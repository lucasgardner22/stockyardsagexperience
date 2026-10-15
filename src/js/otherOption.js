export function initOtherOptions() {
    document.querySelectorAll('[data-other-for]').forEach((otherField) => {
        const form = otherField.closest('form')
        const group = form?.querySelector(`[data-field-handle="${otherField.dataset.otherFor}"]`)
        const radios = [...(group?.querySelectorAll('input[type="radio"]') ?? [])]
        const otherRadio = radios.find((radio) => radio.value === 'other')
        const input = otherField.querySelector('input')

        if (!otherRadio || !input) return

        const emptyRow = otherField.closest('.fui-row')
        const option = otherRadio.parentElement

        const line = document.createElement('div')
        line.classList.add('flex', 'items-center', 'gap-2')
        option.before(line)
        line.append(option, otherField)
        otherField.classList.add('flex-1')

        emptyRow.style.display = 'none'

        otherField.querySelector('label')?.classList.add('sr-only')

        const sync = () => {
            input.required = otherRadio.checked
            if (!otherRadio.checked) input.value = ''
        }

        input.addEventListener('focus', () => {
            otherRadio.checked = true
            otherRadio.dispatchEvent(new Event('change', { bubbles: true }))
        })

        radios.forEach((radio) => radio.addEventListener('change', sync))
        sync()
    })
}