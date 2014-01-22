 (function($) {
	$.extend(true, window, {
		PHPSlickGrid : {
			JSON : {
				GapDataCacheTable : GapDataCacheTable
			}
		}
	});

	function GapDataCacheTable(options,Data) {

		//var self = Data;
		
		// events
		var onRowCountChanged = new Slick.Event();
		var onRowsChanged = new Slick.Event();
		
		Data.onRowCountChanged.subscribe(function (e, args) {
		    linkgrid.updateRowCount();
		    linkgrid.render();
		});


		Data.onRowsChanged.subscribe(function (e, args) {
			linkgrid.invalidateRows(args.rows);
			linkgrid.render();
		});

		
		function getLength() {
			console.log(Data);
			Data.options.table_name=options.table_name;
			return Data.getLength();
		}
		
		function getItem(item) {
			Data.options.table_name=options.table_name;
			var D = Data.getItem(item);
			if (D==null)
				return null;
			var idx = item % 100;
			return D[options.table_name][idx];
		}
		
		return {

			// data provider methods
			"getLength" : getLength,
			"getItem" : getItem,
			"onRowCountChanged" : onRowCountChanged,
			"onRowsChanged" : onRowsChanged,

		};
	}
})(jQuery);