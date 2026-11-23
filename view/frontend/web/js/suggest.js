// Fills the <datalist> of the quick order SKU inputs with the products matching what is typed (SKU or name).
// ponytail: native <datalist>; Safari only matches on the SKU. Build a listbox if name search must work there.
(() => {
    let timer, pending;

    document.addEventListener('input', event => {
        const list = event.target.list,
            query = event.target.value.trim();

        if (!list || !list.dataset.quickorderSuggest || query.length < 2) {
            return;
        }
        clearTimeout(timer);
        timer = setTimeout(async () => {
            // a newer query cancels the one in flight, so a slow answer never replaces a recent one
            pending?.abort();
            pending = new AbortController();
            try {
                const response = await fetch(
                    list.dataset.quickorderSuggest + '?q=' + encodeURIComponent(query),
                    {signal: pending.signal}
                );

                list.replaceChildren(...(await response.json()).map(
                    product => new Option(product.sku + ' — ' + product.name, product.sku)
                ));
            } catch (error) {
                // aborted or failed: suggestions are optional, the field still takes a typed SKU
            }
        }, 200);
    });
})();
