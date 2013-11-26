
document.observe("dom:loaded", function() {
  $$('td.export button.task span').first().update('Export all unprocessed orders');
});
