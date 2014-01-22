 (function($) {
	$.extend(true, window, {
		PHPSlickGrid : {
			JSON : {
				GapDataCacheTable : GapDataCacheTable
			}
		}
	});

	function GapDataCacheTable(options,Data) {

		function getLength() {
			return Data.getLength();
		}
		
		function getItem(item) {
			var D = Data.getItem(item);
			if (D==null)
				return null;
			return D[options.table_name];
		}
		
		return {
			// data provider methods
			"getLength" : getLength,
			"getItem" : getItem
		};
	}
})(jQuery);