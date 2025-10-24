// Function to clear error messages and remove error styling
export function clearErrors() {
    // Select all elements with the 'text-danger' class (typically used for error messages)
    const errorElements = document.querySelectorAll('.text-danger');

    // Loop through each error element and clear its inner HTML (error message)
    errorElements.forEach(el => {
        el.innerHTML = '';
    });

    // Select all input elements with the 'input-error' class (used to highlight fields with errors)
    const inputElements = document.querySelectorAll('.input-error');

    // Loop through each input element and remove the 'input-error' class (removing error styling)
    inputElements.forEach(input => {
        input.classList.remove('input-error');
    });
}
