/**
 * Toggle visibility on elements.
 */
document.addEventListener("DOMContentLoaded", function() {
    Array.prototype.forEach.call(document.getElementsByClassName('httplug-toggle'), function (source) {
        source.addEventListener('click', function() {
            Array.prototype.forEach.call(document.querySelectorAll(source.getAttribute('data-toggle')), function (target) {
                target.classList.toggle('httplug-hidden');
            });
        });
    });
});
