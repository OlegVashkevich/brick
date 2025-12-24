// Button component JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.btn:not(.disabled)');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            console.log('Button clicked:', this.textContent);
        });
    });
});