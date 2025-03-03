const original = window.history.replaceState;
let previousState = null;
window.history.replaceState = function (state, unused, url) {
    state.url = url instanceof URL ? url.toString() : url;
    if (JSON.stringify(state) === JSON.stringify(previousState)) {
        return;
    }
    original.apply(this, [state, unused, url]);
    previousState = state;
};
