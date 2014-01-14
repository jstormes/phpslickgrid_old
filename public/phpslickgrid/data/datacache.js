(function ($) {
	  $.extend(true, window, {
	    PHPSlick: {
	      Data: {
	        DataCache: DataCache
	      }
	    }
	  });


	  /***
	   * A sample Model implementation.
	   * Provides a un-filtered view of the underlying data.
	   * 
	   * Block Math:
	   *     idx = index of the requstes row.  This is just the array index and is 
	   *           known only by this script and the slickgrid
       *
	   *     blockSize = Size of a AJAX request block.  That is the number of rows
	   *                 an AJAX request returns and the size of block in the local
	   *                 buffer. 
	   *
	   *     block = (Math.floor(idx/blockSize)), The block number requested via AJAX
	   *             and stored in the local buffer.
	   *
	   *     blockIdx = index of the block record.
	   *           
	   */
	  function DataCache(options) {

		  // alert(options);
		  //console.log(options);
		  var self = this;

		  
		  var defaults = {
			      jsonrpc: null,
			      upd_dtm_col: null,
			      primay_col: null,
			      blockSize: 100,
			      bufferSize: 10,
//			      pollFrequency: 2500,    // 2.5 Seconds
//			      pollFrequency: 10000,    // 10 Seconds
			      pollFrequency: 1000000,    // 10 Seconds
			      gridName: 'grid',        // Used to tie back to Zend_Session.
			      order_list: {},
			      where_list: new Array()
			    };
		    
		  self.options = $.extend(true, {}, defaults, options);
		  
		  //self.options.primay_col
		  //self.options.upd_dtm_col

		  //console.log(self.options['jsonrpc']);
		  //console.log(options);
		  
		  function setSort(sortarray) {

			  self.options.order_list = sortarray;//$.extend(true, {}, options.order_list, sortarray);
			  //console.log(self.options.order_list);
	      }

	      function setFilter(column,operator,value) {
		      delWhere(column);
		      self.options.where_list.push({'column':column,'operator':operator,'value':value});
	      }

	      function delFilter(column) {
	    	  for (var i=0;i<self.options.where_list.length;i++) {
	    		    if (self.options.where_list[i].column==column)
	    		    	self.options.where_list.remove(i);
	    	  }
		  }

		  function getWhere() {
			 // console.log("getWhere");
			 // console.log(typeof self.options.where_list);
			    return self.options.where_list;
		  }

		  function setIn(column,set,mode) {
			  
	    	  for (var i=0;i<self.options.where_list.length;i++) {
	    		    if (self.options.where_list[i].column==column)
		    		    if (self.options.where_list[i].operator=='in')
		    		        self.options.where_list.splice(i,1);
	    	  }
			  //console.log(set);
			  //searchvalue = split(',',set);
			  self.options.where_list.push({'column':column,'operator':'in','searchvalue':set,'andor':'and'});
		  }

		  function setWhere(filters) {
			  //console.log("inFilters");
			  //console.log(filters);
			  self.options.where_list=new Array();
			  for(var columnName in filters) {
				    for (var j in filters[columnName]){
				        //console.log(columnName);
				        //console.log(filters[columnName][j].operator);
				        self.options.where_list.push({'column':columnName,'operator':filters[columnName][j].operator,'searchvalue':filters[columnName][j].value,'andor':filters[columnName][j].andor});
				    }
			  }
			  //console.log(self.options.where_list);

		  }

	      function invalidate() {
	    	  self.datalength = null;
	    	  self.pages = [];
	    	  self.reverseLookup = [];
	    	  self.activeBuffers = [];
	    	  //self.newestRecord='0';
	      }

		  //console.log(self.options['jsonrpc']);
		  
		    

		    //self.lastBlock=null;

		    // Total number of rows in our dataset
			self.datalength = null;

			// Sparse array of buffers to load data
		    self.pages = new Array();

		    // Service to call on the server side
		    self.service = new jQuery.Zend.jsonrpc({url: self.options['jsonrpc'], async:true, 
		    	//'error': function(data) {alert(data);inFlight=0;},		// Connection error 
			    'error': function(data) {alert('The connection to the server has timed out.  Click OK to try again.');inFlight=0;},		// Connection error 
    		    'exceptionHandler': function(data) {alert(data);} });    // thrown exception.

		    self.newestRecord='0';
		    self.newsetID='0';

		    self.reverseLookup = new Array();

		    self.activeBuffers = new Array();

		    // events
		    var onRowCountChanged = new Slick.Event();
		    var onRowsChanged = new Slick.Event();


		    function getNewest() {
		        function success(data) {
			        //console.log(data.max_updt_dtm);
		            self.newestRecord=data['max_updt_dtm'].max_updt_dtm;
		            self.newestID=data['max_id'].max_id;

		            //console.log('dtm: '+self.newestRecord+" id: "+self.newestID);
		        }

		        self.service.getNewest({'success' : function(data){success(data); } });
		    }

		    getNewest();
	  

		  //options = $.extend(true, {}, null, options);
		  
		  function getLength() {

			  function update(data) {
				  self.datalength=data;
				  //console.log($("#lowerright").text());
				  $("#lowerright").text(self.datalength);
				  

				  onRowCountChanged.notify({previous: 0, current: (self.datalength)}, null, self);
				  
			  }

			  
		        if (self.datalength===null) {
			        var where = new Array();
			        var maxid = {'column':self.primay_col,'operator':'<=','value':self.newestID};
			        where.push(maxid);
		            self.service.getLength(self.options,{'success' : function(data){update(data); } });
		            return 0;
		        }        
		        
		        return (self.datalength-0);
		  }

		  function updateItem(item) {
			  
//			  self.service.setAsync(false);
			  var data=self.service.updateItem(self.newestRecord, item, self.options);
		      
//			  // Create array of updated indices
//		      indices = new Array();
//
//		        
//	          // see if we have record in buffer
//          	  for (i=0;i<data.length;i++) {
//	              // if we have the data in the buffer update it.
//
//		            if (typeof self.reverseLookup["k"+data[i][self.options.primay_col]]!='undefined') {
//			            idx=self.reverseLookup["k"+data[i][self.options.primay_col]];
//			            indices[i]=idx;
//			            block=Math.floor(idx/10);
//			            blockIdx=idx%10;
//			            self.pages[block].data[blockIdx]=data[i];
//			           		            
//			            if (self.newestRecord<data[i][self.options.upd_dtm_col]) {
//			            	self.newestRecord=data[i][self.options.upd_dtm_col];			            	
//			            }
//		            } 
//          	  }
//
//          	  // Tell all subscribers (ie slickgrid) the data change changed for this block	
//          	  if (indices.length>0)	{
//          		onRowsChanged.notify({rows: indices}, null, self);
//          	  }  
//			  self.service.setAsync(true);
			  
		  }
		  
		  function updateDataSync() {
			  self.service.setAsync(false);
			  var data=self.service.getUpdated(self.newestRecord, self.options);
		      
			  // Create array of updated indices
		      var indices = new Array();

		      if (typeof self.pages[block] != 'undefined') {  
		          // see if we have record in buffer
	          	  for (var i=0;i<data.length;i++) {
		              // if we have the data in the buffer update it.
	
			            if (typeof self.reverseLookup["k"+data[i][self.options.primay_col]]!='undefined') {
				            var idx=self.reverseLookup["k"+data[i][self.options.primay_col]];
				            indices[i]=idx;
				            var block=Math.floor(idx/10);
				            var blockIdx=idx%10;
				            
					            self.pages[block].data[self.options.table_name][blockIdx]=data[i];
					           		            
					            if (self.newestRecord<data[i][self.options.upd_dtm_col]) {
					            	self.newestRecord=data[i][self.options.upd_dtm_col];			            	
					            }
				            
			            } 
	          	  }
		      }

          	  // Tell all subscribers (ie slickgrid) the data change changed for this block	
          	  if (indices.length>0)	{
          		onRowsChanged.notify({rows: indices}, null, self);
          	  }  
			  self.service.setAsync(true);
		  }
		  
		  function addItem(item) {
			  //console.log('console.log');
			  //console.log(item);
			  self.service.addItem(item, self.options);
		  }
		  
		    function getItem(item) {

		    	if (self.datalength!=null)
		    	    if (item==(self.datalength)) {
		    	    	//console.log("last item "+self.datalength);
		    	      return null; // For add new row.
		    	    }

		    			    	
		    	var blockSize = 100;

		    	var PendingRequests = new Array(); // FILO queue for request
		    	var inFlight = 0;    // semaphore for access, (Not sure this really works in js).
		    	var newestRecord = '0';

		    	// Successful request comming back from server
		    	// so store it into our local buffer and tell the grid
		    	// to refresh those rows.
		        function success(block,data) {
		            // Set Block of data
		        	console.log("success");
			        console.log(data);
		        	
		        	if (typeof self.pages[block] != 'undefined') {
		        		//if (typeof self.options.table_name != 'undefined') {
		        		//	self.pages[block].data=data[self.options.table_name];
		        		//}
		        		//else {
		        			self.pages[block].data=data;
		        			
		        		//}
			            
			            // Create array of updated indices
				        var indices = new Array();
				        var len=self.pages[block].data[self.options.table_name].length;
				        console.log("Data Len");
				        console.log(self.pages[block].data[self.options.table_name].length);
				        for (var i=0;i<len;i++) {
				            indices[i]=(block*blockSize)+i;
				            // Store the date time of the newest record, we use this later to see if 
				            // we need to refresh the block, column must be named updt_dtm in the db.
				            if (typeof self.pages[block].data[self.options.table_name][i][self.options.upd_dtm_col]!='undefined')
				                if (self.pages[block].data[self.options.table_name][i][self.options.upd_dtm_col] > self.pages[block].updt_dtm) 
				                    self.pages[block].updt_dtm = self.pages[block].data[self.options.table_name][i][self.options.upd_dtm_col];
	
				            // primay key mapping to indices
				            var k_field=self.options.primay_col;
				            self.reverseLookup["k"+self.pages[block].data[self.options.table_name][i][self.options.primay_col]]=(block*blockSize)+i;
				            //console.log("k"+self.pages[block].data[i][k_field]+" "+(block*blockSize)+i);
				        }
				        // Keep a record of the newest record we have seen
				        if (newestRecord<self.pages[block].updt_dtm) 
				        	newestRecord=self.pages[block].updt_dtm;
	
				        // Tell all subscribers (ie slickgrid) the data change changed for this block		        
			        	onRowsChanged.notify({rows: indices}, null, self);

		        	}
		            // POP Request
		        	inFlight--; // Turn semaphore off
			    }

		        // push a request to the server
			    function push(block) {
			        if (!inFlight) {
			        	inFlight++; // Turn semaphor on
			        	self.service.getBlock(block, self.options,{'success':function(data) {success(block,data); }});
			        	//console.log(self.options);
			        }
			    }
			    

			    // currentPage = the currently requested page block
		        var block = Math.floor(item/blockSize);
		        // index of the item requested in the current block
		        var idx=item%blockSize;

		        // If this page block has never been requested create 
		        // a new enpty page for it.
		        if (typeof self.pages[block]=='undefined') {

		        	// Limit the number of rows held in the buffer so we don't 
		        	// run out of memory.
		        	self.activeBuffers.push(block);
		            //console.log('active buffers '+self.activeBuffers);
		            if (self.activeBuffers.length>5) {
			            var toRemove=self.activeBuffers.shift(block);
			            delete self.pages[toRemove];
		            }

			        
			        self.pages[block] = new Object();
		            self.pages[block].data = new Array();
		            self.pages[block].updt_dtm = '0';    // latest data in this block
		                                                 // used to track weather the block
		                                                 // needs to be reloaded.
		            self.pages[block].loaded=false;

		            var d = new Date();
	            
		        }

		        // if the block is not loaded push the request to the
		        // server.
		        if (!self.pages[block].loaded) {
		        	self.pages[block].loaded=true;
			        push(block);
		        }

		        console.log('Returing');
		        console.log(self.pages[block].data[self.options.table_name]);
		    
		       	// return what we have.  If it was blank it will be 
		       	// refreshed by the AJAX call.	  
		        if (typeof self.pages[block].data[self.options.table_name] != 'undefined')
		        	return self.pages[block].data[self.options.table_name][idx];    
		        else return null;
			}

		    //function getItemMetadata(i) {
		    //    return null;
			//}

		    // refresh the data
		    var inFlight=0;
		    setInterval(function() {
		    	
		    	
		    	//console.log(self.options.primay_col);
		    	//console.log(self.newestRecord);

		        function success(data) {
		            inFlight--;

			         // Create array of updated indices
			        var indices = new Array();
			        //console.log('data.length')
			        //console.log(data);
			        
			        if (typeof self.pages[block] != 'undefined') {
		            // see if we have record in buffer
		            	for (var i=0;i<data.length;i++) {
			            	// if we have the data in the buffer update it.
		            		//console.log('id');
		            		//console.log("k"+data[i][self.options.primay_col]);
				            if (typeof self.reverseLookup["k"+data[i][self.options.primay_col]]!='undefined') {
					            var idx=self.reverseLookup["k"+data[i][self.options.primay_col]];
					            indices[i]=idx;
					            var block=Math.floor(idx/10);
					            var blockIdx=idx%10;
					            
					            self.pages[block].data[self.options.table_name][blockIdx]=data[i];
					            
					            if (self.newestRecord<data[i][self.options.upd_dtm_col]) {
					            	self.newestRecord=data[i][self.options.upd_dtm_col];
					            	
					            }
					            
					            	
				                //console.log(data);
				            } 
		            	}
			        }

	            	// Tell all subscribers (ie slickgrid) the data change changed for this block	
	            	if (indices.length>0)	{
	            		//console.log('calling nRowsChanged.notify');
	            		onRowsChanged.notify({rows: indices}, null, self);
	            	}        
	            	    
     
			    }
		    	
			    if (inFlight==0){
				    inFlight++;
				    //console.log("newest");
    		    	//console.log(self.newestRecord);
				    if (Slick.GlobalEditorLock.isActive()==false)
				    	self.service.getUpdated(self.newestRecord,{'success':function(data) {success(data); }});
			    }
		    }, self.options.pollFrequency); // check the server for new data


		  return {

	      // data provider methods
	      "getLength": getLength,
	      "getItem": getItem,
	      //"getItemMetadata": getItemMetadata
	      "setWhere": setWhere,
	      "getWhere": getWhere,
	      "setIn": setIn,
	      "addItem": addItem,
	      "updateDataSync": updateDataSync,

	      // events
	      "onRowCountChanged": onRowCountChanged,
	      "onRowsChanged": onRowsChanged,
	      "setSort": setSort,
	      "invalidate": invalidate,
	      "updateItem": updateItem
	      };
	  }
})(jQuery);