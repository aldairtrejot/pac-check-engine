export function showSpinner() {
    const overlay = document.getElementById('spinnerOverlay') // get the spinner overlay element by its ID
    if (overlay) overlay.style.display = 'flex' // show the spinner by setting display to 'flex'
}

export function hideSpinner() {
    const overlay = document.getElementById('spinnerOverlay') // get the spinner overlay element by its ID
    if (overlay) overlay.style.display = 'none' // hide the spinner by setting display to 'none'
}
