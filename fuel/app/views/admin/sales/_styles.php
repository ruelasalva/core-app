    <style>
        .quote-section-title { color: #fff; font-weight: 700; padding: 14px 18px; margin: 0 -1rem 1rem; }
        .quote-section-partner { background: #5b6ee1; }
        .quote-section-values { background: #15bfd2; }
        .quote-section-products { background: #ff5b42; }
        .quote-partner-panel { background: #fff; border-radius: 6px; padding: 12px; margin-bottom: 18px; }
        .quote-product-capture { background: #fff; border: 1px solid #e0e6ef; border-radius: 6px; padding: 14px; margin-bottom: 14px; }
        .quote-search-wrap { position: relative; }
        .quote-search-results { position: absolute; z-index: 1060; left: 0; right: 0; max-height: 280px; overflow: auto; background: #fff; border: 1px solid #cfd8e3; border-radius: 0 0 6px 6px; box-shadow: 0 10px 24px rgba(15,23,42,.16); }
        .quote-search-result { display: grid; grid-template-columns: 52px 1fr auto; gap: 10px; align-items: center; width: 100%; border: 0; border-bottom: 1px solid #edf1f5; background: #fff; text-align: left; padding: 8px; }
        .quote-search-result:hover { background: #f5f8fb; }
        .quote-search-result img { width: 52px; height: 42px; object-fit: cover; border-radius: 5px; border: 1px solid #dde3ea; }
        .quote-selected-product { display: grid; grid-template-columns: 132px 1fr; gap: 14px; align-items: start; min-height: 132px; }
        .quote-selected-product img { width: 132px; height: 112px; object-fit: cover; border: 1px solid #dde3ea; border-radius: 6px; background: #eef3f7; }
        .quote-items-panel { background: #fff; border: 1px solid #e0e6ef; border-radius: 6px; padding: 12px; margin-bottom: 14px; }
        .quote-workbench { display: grid; grid-template-columns: minmax(0, 1.25fr) 420px; gap: 16px; }
        .quote-product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; max-height: 52vh; overflow: auto; padding-right: 4px; }
        .quote-product-card { border: 1px solid #dde3ea; border-radius: 8px; background: #fff; overflow: hidden; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
        .quote-product-card:hover, .quote-product-card.active { border-color: #007bff; box-shadow: 0 6px 16px rgba(15,23,42,.10); }
        .quote-product-card img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; background: #eef3f7; }
        .quote-product-body { padding: 9px; }
        .quote-product-title { font-size: .88rem; line-height: 1.25; font-weight: 700; min-height: 36px; }
        .quote-meta { display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; font-size: .78rem; color: #6c757d; }
        .quote-thumb { width: 54px; height: 44px; border-radius: 6px; border: 1px solid #dde3ea; object-fit: cover; background: #eef3f7; }
        .quote-cart { position: sticky; top: 12px; }
        .quote-toolbar { display: grid; grid-template-columns: 1.3fr 1fr 1fr auto; gap: 8px; align-items: end; }
        .quote-modal-fullscreen { width: calc(100vw - 24px); max-width: calc(100vw - 24px); margin: 12px auto; }
        .quote-modal-fullscreen .modal-content { min-height: calc(100vh - 24px); }
        .quote-modal-fullscreen .modal-body { max-height: calc(100vh - 156px); overflow: auto; }
        .quote-page-capture { margin: -10px -7.5px 0; }
        .quote-page-capture .modal-content { min-height: calc(100vh - 150px); border: 0; border-radius: 0; box-shadow: none; }
        .quote-page-capture .modal-body { min-height: calc(100vh - 280px); overflow: visible; }
        .quote-page-capture .quote-product-grid { max-height: none; overflow: visible; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); }
        .quote-page-capture .quote-workbench { grid-template-columns: minmax(0, 1fr) 360px; }
        .price-hidden .money-cell, .price-hidden .price-text { display: none; }
        .range-chip { display: inline-block; border: 1px solid #dee2e6; border-radius: 999px; padding: 2px 7px; margin: 2px 2px 0 0; font-size: .72rem; color: #495057; background: #f8f9fa; cursor: pointer; }
        .range-chip:hover { border-color: #007bff; color: #0056b3; }
        @media (max-width: 1100px) { .quote-workbench { grid-template-columns: 1fr; } .quote-cart { position: static; } .quote-selected-product { grid-template-columns: 1fr; } }
    </style>
