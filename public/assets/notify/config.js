const notyfBl = new Notyf({
    position: {
        x: 'right',
        y: 'top',
    },
    dismissible: false,
    duration: 6000,
});


const msg = sessionStorage.getItem('nofify_message');
if (msg) {
    notyfBl.success(msg);
    sessionStorage.removeItem('nofify_message');
}