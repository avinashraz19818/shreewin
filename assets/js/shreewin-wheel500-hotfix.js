(function () {
    'use strict';

    var rewardText = 'Get ₹500';

    function applyWheelReward() {
        var labels = document.querySelectorAll('#app .turntable-text');
        labels.forEach(function (label) {
            if (label.textContent.trim() !== rewardText) {
                label.textContent = rewardText;
            }
            label.setAttribute('aria-label', rewardText);
        });
    }

    function start() {
        applyWheelReward();
        var observer = new MutationObserver(applyWheelReward);
        observer.observe(document.documentElement, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
}());
