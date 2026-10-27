<?php
/**
 * One-off importer: copies posts from the old WordPress site into the
 * What's Happening section.
 *
 *   php scripts/import-wordpress.php --dry-run      Report what would happen, change nothing
 *   php scripts/import-wordpress.php --limit=1      Import only the first new post (for testing)
 *   php scripts/import-wordpress.php                Import everything
 *
 * - Skips posts that already exist in the Insiders section (matched by their text).
 * - Fills existing What's Happening entries that have no content yet; skips ones that do.
 * - Safe to re-run: posts already imported (matched by slug) are skipped.
 * - Downloads every image into Assets → Stockyards Images → whats-happening.
 * - Each post's content goes into one Text Block, with images and YouTube videos
 *   as nested Single Image / Video entries, like posts built by hand.
 * - Summaries come from the first real paragraph of each post.
 * - Bylines come from the WordPress author ("stockyards" becomes "Stockyards Ag Experience").
 * - Writes storage/wp-import-report.csv (including each post's original author).
 */

require dirname(__DIR__) . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use craft\models\VolumeFolder;

const WP_API = 'https://www.stockyardsagexperience.org/wp-json/wp/v2/posts';
const SECTION = 'whatsHappening';
const ARTICLE_TYPE = 'happeningArticle';
const ASSET_FOLDER = 'whats-happening';
const SUMMARY_LENGTH = 240;
const DUPLICATE_OVERLAP = 0.5;

$options = getopt('', ['dry-run', 'limit:', 'slug:']);
$dryRun = isset($options['dry-run']);
$limit = isset($options['limit']) ? (int)$options['limit'] : null;
$onlySlug = $options['slug'] ?? null;
$skipSlugs = ['hello-world']; // WordPress's default sample post

$craft = Craft::$app;
$http = Craft::createGuzzleClient(['timeout' => 60]);

// ---------------------------------------------------------------------------
// Look up the Craft pieces we need
// ---------------------------------------------------------------------------

$section = $craft->getEntries()->getSectionByHandle(SECTION) or fail('Section not found: ' . SECTION);
$articleType = collect($section->getEntryTypes())->firstWhere('handle', ARTICLE_TYPE) or fail('Entry type not found: ' . ARTICLE_TYPE);
$fields = $craft->getFields();
$contentBuilderField = $fields->getFieldByHandle('contentBuilder') or fail('Field not found: contentBuilder');
$bodyField = $fields->getFieldByHandle('body') or fail('Field not found: body');
$entriesService = $craft->getEntries();
$textBlockType = $entriesService->getEntryTypeByHandle('textBlock') or fail('Entry type not found: textBlock');
$singleImageType = $entriesService->getEntryTypeByHandle('singleImage') or fail('Entry type not found: singleImage');
$videoType = $entriesService->getEntryTypeByHandle('video') or fail('Entry type not found: video');
$volume = $craft->getVolumes()->getVolumeByHandle('stockyardsImages') or fail('Volume not found: stockyardsImages');
$adminId = craft\elements\User::find()->admin()->status(null)->ids()[0] ?? fail('No admin user found');

// ---------------------------------------------------------------------------
// Fetch every WordPress post (100 per request is the API maximum)
// ---------------------------------------------------------------------------

$posts = [];
for ($page = 1; ; $page++) {
    $response = $http->get(WP_API, ['query' => ['per_page' => 100, 'page' => $page, '_embed' => 'author,wp:featuredmedia']]);
    $posts = array_merge($posts, json_decode((string)$response->getBody(), true));
    if ($page >= (int)$response->getHeaderLine('X-WP-TotalPages')) {
        break;
    }
}
usort($posts, fn($a, $b) => strcmp($a['date_gmt'], $b['date_gmt']));
echo count($posts), " WordPress posts fetched\n";

// ---------------------------------------------------------------------------
// Build a fingerprint of every Insiders post so duplicates can be skipped
// ---------------------------------------------------------------------------

$insiderFingerprints = [];
foreach (Entry::find()->section('insiders')->type('insiderPost')->status(null)->all() as $insiderPost) {
    $text = '';
    foreach ($insiderPost->getFieldValue('contentBuilder')->status(null)->all() as $block) {
        if ($block->type->handle === 'textBlock') {
            $text .= ' ' . (string)$block->getFieldValue('body');
        }
    }
    $insiderFingerprints[$insiderPost->id] = [$insiderPost->title, shingles($text)];
}

// ---------------------------------------------------------------------------
// Import
// ---------------------------------------------------------------------------

$report = [['wp_id', 'wp_url', 'title', 'post_date', 'wp_author', 'action', 'craft_entry_id', 'images', 'notes']];
$counts = ['imported' => 0, 'filled' => 0, 'insider duplicate' => 0, 'already exists' => 0, 'failed' => 0];
$processed = 0;

foreach ($posts as $post) {
    $title = cleanText($post['title']['rendered']);
    $author = $post['_embedded']['author'][0]['name'] ?? '';
    $postDate = new DateTime($post['date_gmt'], new DateTimeZone('UTC'));
    $row = fn($action, $id = '', $images = '', $notes = '') => [$post['id'], $post['link'], $title, $postDate->format('Y-m-d'), $author, $action, $id, $images, $notes];

    if ($onlySlug !== null && $post['slug'] !== $onlySlug) {
        continue;
    }
    if (in_array($post['slug'], $skipSlugs, true)) {
        $report[] = $row('skipped: WordPress sample post');
        continue;
    }

    // 1. Duplicate of an Insiders post?
    $fingerprint = shingles($post['content']['rendered']);
    foreach ($insiderFingerprints as $insiderId => [$insiderTitle, $insiderShingles]) {
        if (overlap($fingerprint, $insiderShingles) >= DUPLICATE_OVERLAP) {
            $counts['insider duplicate']++;
            $report[] = $row('skipped: duplicate of Insiders post', $insiderId, '', $insiderTitle);
            continue 2;
        }
    }

    // 2. Already in What's Happening?
    $existing = Entry::find()->section(SECTION)->slug($post['slug'])->status(null)->one()
        ?? findByTitle($title, array_column($posts, 'slug'));
    if ($existing && $existing->getFieldValue('contentBuilder')->status(null)->count() > 0) {
        $counts['already exists']++;
        $report[] = $row('skipped: already has content', $existing->id);
        continue;
    }

    if ($limit !== null && $processed >= $limit) {
        continue;
    }
    $processed++;

    // 3. Convert the content
    [$html, $media, $notes] = convertContent($post['content']['rendered']);
    $summary = makeSummary($html);
    $featuredUrl = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null;
    $imageCount = count(array_filter($media, fn($m) => $m['type'] === 'image')) + ($featuredUrl ? 1 : 0);

    if ($dryRun) {
        $action = $existing ? 'would fill existing entry' : 'would import';
        $counts[$existing ? 'filled' : 'imported']++;
        printf("%s  %s  %s  (%d images%s)\n", $postDate->format('Y-m-d'), str_pad($action, 26), $title, $imageCount, $notes ? '; ' . implode('; ', $notes) : '');
        $report[] = $row($action, $existing?->id ?? '', $imageCount, implode('; ', $notes));
        continue;
    }

    $transaction = $craft->getDb()->beginTransaction();
    try {
        // The article itself
        $article = $existing ?? new Entry([
            'sectionId' => $section->id,
            'typeId' => $articleType->id,
        ]);
        $article->title = $title;
        $article->slug = $post['slug'];
        $article->postDate = $postDate;
        $article->setAuthorIds([$adminId]);
        $article->setFieldValue('summary', $summary);
        $article->setFieldValue('byline', ['stockyards' => 'Stockyards Ag Experience'][$author] ?? $author);
        if ($featuredUrl && ($featured = importImage($featuredUrl, $post['_embedded']['wp:featuredmedia'][0]['alt_text'] ?? ''))) {
            $article->setFieldValue('image', [$featured->id]);
        }
        saveOrFail($article);

        // One Text Block holding the whole post
        $textBlock = new Entry([
            'fieldId' => $contentBuilderField->id,
            'ownerId' => $article->id,
            'typeId' => $textBlockType->id,
            'siteId' => $article->siteId,
        ]);
        $textBlock->setFieldValue('body', '');
        saveOrFail($textBlock);

        // Images and videos become nested entries inside the Text Block's Body
        $tags = [];
        foreach ($media as $i => $item) {
            $nested = new Entry([
                'fieldId' => $bodyField->id,
                'ownerId' => $textBlock->id,
                'siteId' => $article->siteId,
                'typeId' => $item['type'] === 'image' ? $singleImageType->id : $videoType->id,
            ]);
            if ($item['type'] === 'image') {
                $asset = importImage($item['url'], $item['alt']);
                if (!$asset) {
                    $notes[] = 'could not download ' . $item['url'];
                    $tags[$i] = '';
                    continue;
                }
                $nested->setFieldValue('image', [$asset->id]);
            } else {
                $nested->title = $title; // Video entries require a title ("Video Description")
                $nested->setFieldValue('videoUrl', ['type' => 'url', 'value' => $item['url']]);
            }
            if ($item['caption'] !== '') {
                $nested->setFieldValue('caption', $item['caption']);
            }
            saveOrFail($nested);
            $tags[$i] = sprintf('<craft-entry data-entry-id="%d">&nbsp;</craft-entry>', $nested->id);
        }

        $textBlock->setFieldValue('body', preg_replace_callback('/\[\[\[MEDIA:(\d+)]]]/', fn($m) => $tags[$m[1]] ?? '', $html));
        saveOrFail($textBlock);

        $transaction->commit();
        $counts[$existing ? 'filled' : 'imported']++;
        printf("%s  %-18s %s (#%d)\n", $postDate->format('Y-m-d'), $existing ? 'filled existing' : 'imported', $title, $article->id);
        $report[] = $row($existing ? 'filled existing entry' : 'imported', $article->id, $imageCount, implode('; ', $notes));
    } catch (Throwable $e) {
        $transaction->rollBack();
        $counts['failed']++;
        printf("FAILED  %s: %s\n", $title, $e->getMessage());
        $report[] = $row('FAILED', '', '', $e->getMessage());
    }
}

$reportPath = $craft->getPath()->getStoragePath() . '/wp-import-report.csv';
$handle = fopen($reportPath, 'w');
foreach ($report as $line) {
    fputcsv($handle, $line);
}
fclose($handle);

echo "\n", $dryRun ? "DRY RUN — nothing was saved.\n" : '';
foreach ($counts as $label => $count) {
    printf("%-18s %d\n", $label, $count);
}
echo "Report: $reportPath\n";

// ===========================================================================
// Helpers
// ===========================================================================

function fail(string $message): never
{
    fwrite(STDERR, "Error: $message\n");
    exit(1);
}

function saveOrFail(craft\base\ElementInterface $element): void
{
    if (!Craft::$app->getElements()->saveElement($element)) {
        throw new RuntimeException(get_class($element) . ': ' . implode(' ', $element->getFirstErrors()));
    }
}

function cleanText(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $text)));
}

/** Six-word chunks of a post's text, used to recognise the same post in two places. */
function shingles(string $html): array
{
    $text = mb_strtolower(str_replace('’', "'", cleanText(preg_replace('/<[^>]+>/', ' ', $html))));
    preg_match_all("/[a-z0-9']+/", $text, $m);
    $words = $m[0];
    $set = [];
    for ($i = 0; $i + 6 <= count($words); $i++) {
        $set[implode(' ', array_slice($words, $i, 6))] = true;
    }
    return $set;
}

function overlap(array $a, array $b): float
{
    if (!$a || !$b) {
        return 0;
    }
    return count(array_intersect_key($a, $b)) / min(count($a), count($b));
}

/**
 * Finds an entry that was created by hand (not by this importer) with the same title.
 * Entries whose slug matches a WordPress post were imported, so they're ignored —
 * otherwise two WordPress posts that share a title would be treated as one.
 */
function findByTitle(string $title, array $wpSlugs): ?Entry
{
    $normalise = fn($t) => trim(preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($t)));
    foreach (Entry::find()->section(SECTION)->status(null)->all() as $entry) {
        if (in_array($entry->slug, $wpSlugs, true)) {
            continue;
        }
        if ($normalise($entry->title) === $normalise($title)) {
            return $entry;
        }
    }
    return null;
}

/**
 * Turns WordPress/Avada HTML into clean CKEditor HTML.
 * Images and videos are swapped for [[[MEDIA:n]]] markers and returned separately.
 *
 * @return array{string, array, string[]}
 */
function convertContent(string $html): array
{
    $media = [];
    $notes = [];

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="wp-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);
    $root = $doc->getElementById('wp-root');

    foreach (iterator_to_array($xpath->query('//script|//style|//noscript')) as $node) {
        $node->parentNode->removeChild($node);
    }

    $marker = function(array $item) use (&$media, $doc): DOMText {
        $media[] = $item;
        return $doc->createTextNode('[[[MEDIA:' . (count($media) - 1) . ']]]');
    };

    // Captioned images (WordPress wraps them in div.wp-caption)
    foreach (iterator_to_array($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wp-caption ")]')) as $wrapper) {
        $img = $xpath->query('.//img', $wrapper)->item(0);
        $captionNode = $xpath->query('.//*[contains(@class, "wp-caption-text")]', $wrapper)->item(0);
        if ($img) {
            $wrapper->parentNode->replaceChild($marker([
                'type' => 'image',
                'url' => bestImageUrl($img),
                'alt' => $img->getAttribute('alt'),
                'caption' => $captionNode ? cleanText($doc->saveHTML($captionNode)) : '',
            ]), $wrapper);
        }
    }

    // Remaining images (a link wrapped around just an image is dropped with it)
    foreach (iterator_to_array($xpath->query('//img')) as $img) {
        $target = $img;
        if ($img->parentNode->nodeName === 'a' && trim($img->parentNode->textContent) === '') {
            $target = $img->parentNode;
        }
        $target->parentNode->replaceChild($marker([
            'type' => 'image',
            'url' => bestImageUrl($img),
            'alt' => $img->getAttribute('alt'),
            'caption' => '',
        ]), $target);
    }

    // Embedded videos: YouTube becomes a Video entry, anything else a plain link
    foreach (iterator_to_array($xpath->query('//iframe')) as $iframe) {
        $src = $iframe->getAttribute('src');
        if (preg_match('~youtube(?:-nocookie)?\.com/embed/([\w-]{11})~', $src, $m)) {
            $iframe->parentNode->replaceChild($marker([
                'type' => 'video',
                'url' => 'https://www.youtube.com/watch?v=' . $m[1],
                'alt' => '',
                'caption' => '',
            ]), $iframe);
        } else {
            $p = $doc->createElement('p');
            $a = $doc->createElement('a', 'Watch the video');
            $a->setAttribute('href', $src);
            $p->appendChild($a);
            $iframe->parentNode->replaceChild($p, $iframe);
            $notes[] = 'non-YouTube video replaced with a link';
        }
    }

    // Unwrap layout elements, keep only content tags, strip styling attributes
    $allowed = ['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a', 'strong', 'em', 'b', 'i', 'u', 'blockquote', 'br', 'hr', 'sub', 'sup'];
    $rename = ['h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4'];
    foreach (iterator_to_array($xpath->query('.//*', $root)) as $el) {
        if (!$el->parentNode) {
            continue;
        }
        $tag = strtolower($el->nodeName);
        if (isset($rename[$tag])) {
            $replacement = $doc->createElement($rename[$tag]);
            while ($el->firstChild) {
                $replacement->appendChild($el->firstChild);
            }
            $el->parentNode->replaceChild($replacement, $el);
            continue;
        }
        if (!in_array($tag, $allowed, true)) {
            while ($el->firstChild) {
                $el->parentNode->insertBefore($el->firstChild, $el);
            }
            $el->parentNode->removeChild($el);
            continue;
        }
        foreach (iterator_to_array($el->attributes) as $attr) {
            if (!($tag === 'a' && $attr->name === 'href')) {
                $el->removeAttribute($attr->name);
            }
        }
    }

    // Wrap stray text (left over from unwrapped divs) in paragraphs
    $blockTags = ['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'blockquote', 'hr'];
    $pending = null;
    foreach (iterator_to_array($root->childNodes) as $child) {
        $isBlock = $child instanceof DOMElement && in_array(strtolower($child->nodeName), $blockTags, true);
        if ($isBlock) {
            $pending = null;
            continue;
        }
        if ($pending === null) {
            if ($child instanceof DOMText && trim($child->textContent, " \n\r\t\u{00A0}") === '') {
                continue;
            }
            $pending = $doc->createElement('p');
            $root->insertBefore($pending, $child);
        }
        $pending->appendChild($child);
    }

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }

    // Media markers stand on their own between paragraphs
    $out = preg_replace('/(\[\[\[MEDIA:\d+]]])/', '</p>$1<p>', $out);
    $out = preg_replace('~<p>\s*(<br\s*/?>\s*)*</p>~', '', $out);
    $out = preg_replace('~<p>(?:\s|&nbsp;|\x{00A0})*</p>~u', '', $out);
    $out = preg_replace('~(<br\s*/?>\s*){3,}~', '<br><br>', $out);
    // Markers directly inside a list item etc. are fine as-is; tidy whitespace
    $out = trim(preg_replace("/\n{2,}/", "\n", $out));

    return [$out, $media, array_values(array_unique($notes))];
}

/** The largest version WordPress offers for an <img>. */
function bestImageUrl(DOMElement $img): string
{
    $best = $img->getAttribute('src');
    $bestWidth = 0;
    foreach (explode(',', $img->getAttribute('srcset')) as $candidate) {
        if (preg_match('/^\s*(\S+)\s+(\d+)w\s*$/', $candidate, $m) && (int)$m[2] > $bestWidth) {
            [$best, $bestWidth] = [$m[1], (int)$m[2]];
        }
    }
    return $best;
}

function makeSummary(string $html): string
{
    preg_match_all('~<p>(.*?)</p>~s', $html, $m);
    foreach ($m[1] as $paragraph) {
        $text = cleanText($paragraph);
        if (mb_strlen($text) < 40 || str_contains($text, '[[[MEDIA:')) {
            continue;
        }
        if (mb_strlen($text) <= SUMMARY_LENGTH) {
            return $text;
        }
        $cut = mb_substr($text, 0, SUMMARY_LENGTH);
        $cut = mb_substr($cut, 0, mb_strrpos($cut, ' ') ?: SUMMARY_LENGTH);
        return rtrim($cut, " ,;:-–—") . '…';
    }
    return '';
}

/** Downloads an image into Assets (reusing it if it was imported before). */
function importImage(string $url, string $alt = ''): ?Asset
{
    static $folder = null;
    $craft = Craft::$app;
    global $volume, $http;

    if ($folder === null) {
        $rootFolder = $craft->getAssets()->getRootFolderByVolumeId($volume->id);
        $folder = $craft->getAssets()->findFolder(['parentId' => $rootFolder->id, 'name' => ASSET_FOLDER]);
        if (!$folder) {
            $folder = new VolumeFolder([
                'parentId' => $rootFolder->id,
                'volumeId' => $volume->id,
                'name' => ASSET_FOLDER,
                'path' => ASSET_FOLDER . '/',
            ]);
            $craft->getAssets()->createFolder($folder);
        }
    }

    $filename = AssetsHelper::prepareAssetName(rawurldecode(basename(parse_url($url, PHP_URL_PATH))));
    $existing = Asset::find()->folderId($folder->id)->filename($filename)->one();
    if ($existing) {
        return $existing;
    }

    $tempPath = $craft->getPath()->getTempPath() . '/' . uniqid('wp-', true) . '-' . $filename;
    try {
        $http->get($url, ['sink' => $tempPath]);
    } catch (Throwable) {
        FileHelper::unlink($tempPath);
        return null;
    }

    $asset = new Asset();
    $asset->tempFilePath = $tempPath;
    $asset->filename = $filename;
    $asset->newFolderId = $folder->id;
    $asset->setVolumeId($volume->id);
    $asset->avoidFilenameConflicts = true;
    $asset->alt = $alt !== '' ? $alt : null;
    $asset->setScenario(Asset::SCENARIO_CREATE);

    return $craft->getElements()->saveElement($asset) ? $asset : null;
}
