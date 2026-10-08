export function initReviewCycles() {
    document.querySelectorAll('[data-review-cycle]').forEach((section) => {
        const pages = section.querySelectorAll('[data-review-page]')
        if (pages.length < 2) return

        const interval = Number(section.dataset.interval) || 6000
        let current = 0
        let timer = null

        function show(index) {
            pages.forEach((page, i) => {
                const isActive = i === index
                page.classList.toggle('opacity-100', isActive)
                page.classList.toggle('opacity-0', !isActive)
                page.classList.toggle('pointer-events-none', !isActive)
                page.toggleAttribute('inert', !isActive)
            })
            current = index
        }

        function next() {
            show((current + 1) % pages.length)
        }

        function start() {
            timer = setInterval(next, interval)
        }

        function stop() {
            clearInterval(timer)
        }

        section.addEventListener('mouseenter', stop)
        section.addEventListener('mouseleave', start)

        start()
    })
}