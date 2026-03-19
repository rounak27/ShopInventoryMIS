const INIT=(()=>{
  function init() {
  ItemMgr.init();
  CatMgr.init();
  StockMgr.init();
  PurchaseMgr.init();
  HistoryMgr.init();
    }
  return { init};
})();