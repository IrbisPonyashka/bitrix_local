(function() {
	if ( BX.Type.isNull(BX.SidePanel.Instance.getSliderByWindow(window)) )
	{
		BX.SidePanel.Instance.bindAnchors({
			rules: [
				{
					condition: [ new RegExp("/crm/extratype/.*", "i") ],
					options: {
						cacheable: false
					}
				}
			]
		});
	}
})();