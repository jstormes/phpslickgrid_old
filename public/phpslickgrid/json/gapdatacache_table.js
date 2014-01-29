 (function($) {
	$.extend(true, window, {
		PHPSlickGrid : {
			JSON : {
				GapDataCacheTable : GapDataCacheTable
			}
		}
	});

	function GapDataCacheTable(options,Data) {

		var self = this;

		var defaults = {
			jsonrpc : null,
			upd_dtm_col : null,
			primay_col : null,
			blockSize : 100,
			bufferSize : 10,
			pollFrequency : 1000000, // 1000 Seconds
			gridName : 'grid', // Used to tie back to Zend_Session.
			order_list : {},
			where_list : new Array()
		};

		self.options = $.extend(true, {}, defaults, options);

		// events
		//var onRowCountChanged = new Slick.Event();
		//var onRowsChanged = new Slick.Event();

		// Pages of our data
		self.pages = new Array();
		self.reverseLookup = new Array();
		self.newestRecord = '0';

		// Service to call on the server side
		self.service = new jQuery.Zend.jsonrpc({
			url : self.options['jsonrpc'],
			async : true,
			// Connection error
			'error' : function(data) {
				// alert('The connection to the server has timed out. Click OK
				// to try again.');
				// inFlight = 0;
			}, // Connection error
			'exceptionHandler' : function(data) {
				alert(data);
			}
		}); // thrown exception.

		// Total number of rows in our dataset
		self.datalength = null;
		self.lengthdate = null;
		
		/** Use a common get length **/
		function getLength() {
			return Data.getLength();
		}
		
		/** Use a common get item **/
		function getItem(item) {
			var D = Data.getItem(item);
			if (D==null)
				return null;
			return D[options.table_name];
		}
		
		/** Let each grid use it's own update **/
		function updateItem(item) {
			self.service.updateItem(self.newestRecord, item, self.options);
		}
		
		/** Let each grid use it's own add **/
		function addItem(item) {
			self.service.addItem(item, self.options);
		}
		
		return {
			// data provider methods
			"getLength" : getLength,
			"getItem" : getItem,
			"addItem": addItem,
			"updateItem": updateItem
		};
	}
})(jQuery);