/**
 * Datacenter Dashboard Metrics Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    // Number counter animation
    const counters = document.querySelectorAll('.counter-val');
    counters.forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-target') || counter.innerText);
        let count = 0;
        const speed = 20;
        const increment = target / speed;

        if (isNaN(target)) return;

        const updateCount = () => {
            count += increment;
            if (count < target) {
                counter.innerText = Number.isInteger(target) ? Math.ceil(count) : count.toFixed(1);
                setTimeout(updateCount, 40);
            } else {
                counter.innerText = target;
            }
        };
        updateCount();
    });
});
