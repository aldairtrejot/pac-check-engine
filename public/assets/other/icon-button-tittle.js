document.querySelectorAll('.icon-button').forEach(el => {
    el.setAttribute('data-title', el.getAttribute('title'));
    el.removeAttribute('title');
});
