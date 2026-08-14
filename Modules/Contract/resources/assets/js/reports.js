$(function () {

  var currYear = new Date().getFullYear();
  var loopLength = 0;
  var donutChart;
  var donutChartConfig;
  var donutChartEl;
  _deleteCookie('exportUrl');
  _deleteCookie('exportParams');  
  if (jQuery.fn.select2) {
    $('.select2').select2();
  }

  var dt_exception_table = $('.exception-table'),
    departmentTree = $('#departmentTree');
    
    var contractValueTree = $('#contractValueTree');
    var contractDetailTree = $('#contractDetailTree');

  loadStatusList();    
  
  loadExpiredData();

 let currLocation = $('#currentLocation').val();
 loadLocationList(0, currLocation);

  loadExceptionList();

  loadDepartmentList();
  
  loadTagsLocationList();
  
  loadLocationValueList();
  
  loadClausesContractList();

  if (dt_exception_table.length) {
    dt_exception_table.DataTable();
  }

  if (_getCookie('filterByYear')) {
    $('#yearSelected').text(_getCookie('filterByYear'));
    $(`[data-year='${_getCookie('filterByYear')}']`).addClass('active');
  }else{
    _setCookie('filterByYear', currYear);
    $('#yearSelected').text(currYear);
    $(`[data-year='${currYear}']`).addClass('active');
  }
  
  
    $(document).ready(function() {
        if (contractDetailTree.length) {

            $('#contractDetailTree')
              .jstree({
                core: {
                  check_callback: true,
                  data: function (node, cb) {
                    let nodeId = node && node.id ? node.id : '#';
                    $.ajax({
                      url: APP_URL + '/contracts/reports-contract-value-tree',
                      data: {
                        nodeid: nodeId,
                        show_gt_zero: $('#showAllCounts').is(':checked') ? 0 : 1
                      },
                      success: function(res) {
                        let cleaned = res.filter(n => n !== null);
                        cb(cleaned);
                      }
                    });
                  },
                  themes: {
                    stripes: true,
                    variant: "large"
                  }
                },
                types: {
                  department: { icon: "ti ti-building text-primary" },
                  type: { icon: "ti ti-file-description text-success" },
                  location: { icon: "ti ti-map-pin text-warning" }
                },
                plugins: ["types"]
              })
              .on('loaded.jstree', function (e, data) {
                // Get instance and root nodes
                const instance = data.instance;
                const rootNode = instance.get_node('#');
            
                // Sometimes root node itself has no data, so pick first child (first department)
                if (rootNode.children.length) {
                  const firstNode = instance.get_node(rootNode.children[0]);
            
                  // Collect children data for chart
                  const childrenData = firstNode.children.map(childId => {
                    const child = instance.get_node(childId);
                    return {
                      label: child.data.textCustom,
                      value: parseFloat(child.data.total_amount || 0)
                    };
                  });
            
                  const parentLabel = firstNode.data.textCustom || firstNode.text;
                  const parentValue = parseFloat(firstNode.data?.total_amount || 0);
            
                  renderDonutChart(parentLabel, parentValue, childrenData);
                }
              })
              .on('open_node.jstree', function (e, data) {
                const instance = data.instance;
                const openedNode = data.node;
                const parentNode = instance.get_node(openedNode.parent);
            
                // Close sibling nodes only
                parentNode.children.forEach(siblingId => {
                  if (siblingId !== openedNode.id) {
                    const siblingNode = instance.get_node(siblingId);
                    if (siblingNode.state.opened) {
                      instance.close_node(siblingId);
                    }
                  }
                });
              })
              .on('close_node.jstree', function (e, data) {
                data.node.state.loaded = false;
              })
              .on('select_node.jstree', function(e, data) {
                const parentNode = data.node;
                const instance = data.instance;
            
                const childrenIds = parentNode.children || [];
                let childrenData = [];
            
                childrenIds.forEach(childId => {
                  const child = instance.get_node(childId);
                  if (child?.data?.total_amount !== undefined) {
                    childrenData.push({
                      label: child.data.textCustom,
                      value: parseFloat(child.data.total_amount)
                    });
                  }
                });
            
                const parentLabel = parentNode.data.textCustom || parentNode.text;
                const parentValue = parseFloat(parentNode.data?.total_amount || 0);
                //dept_1,type_1_1
                renderDonutChart(parentLabel, parentValue, childrenData);
                loadDetailReportList({nodeid:data.node.id}, 'departmentDetailReport', '5');
              });

        }
        if (contractValueTree.length) {
        document.getElementById('loadReport').style.visibility="visible";
    	    $.ajax({
    		url: APP_URL + '/contracts/reports-contract-value?loadTree=1',
    		type: 'GET',
    		success: function (response) {
    		    document.getElementById('loadReport').style.visibility="hidden";
    			$('#contractValueTree ul').html(response.treedata);
    		  if (contractValueTree.length) {
    			contractValueTree.on('open_node.jstree', function (e, data) {
    				console.log(data);
    				let tabToggle = $('.available-toggle.active-toggle').data('toggle-tb');
    				toggleTabAvailable(tabToggle);
    				let cdeptData = JSON.parse(data.node.li_attr['data-cdept'] ?? '');
            	    $.ajax({
                		url: APP_URL + '/contracts/reports-contract-value?loadTree=1',
                		type: 'GET',
                		data: cdeptData,
                		success: function (response) {
                		    
                		}
            		});
    			}).on("select_node.jstree", function (e, data) {
    				let cdeptData = JSON.parse(data.node.li_attr['data-cdept'] ?? '');
    				let compareSelectionDro = (data.node.li_attr['data-par-dropdown'] ?? '').split(",");
    				let compareSelectionTxt = (data.node.li_attr['data-par-text'] ?? '').split(",");
    				let compareSelectionVal = (data.node.li_attr['data-par-val'] ?? '').split(",");
    				let currSelDrop = compareSelectionDro.pop();
    				let currSelText = compareSelectionTxt.pop();
    				let currSelValu = compareSelectionVal.pop();
    				$('#comparisionSelect').html(`<option value="0" data-all-val='${compareSelectionVal.length > 0 ? compareSelectionVal[0] : 0}'>All</option>`);
    				compareSelectionTxt.forEach((vl,idx)=>{
    				    let isLastElement = idx == compareSelectionTxt.length -1;
    					$('#comparisionSelect').append(`<option data-chart-text="${vl},${currSelText}" data-chart-val="${compareSelectionVal[idx]},${currSelValu}" ${ isLastElement ? 'selected': '' } value="${compareSelectionVal[idx]}">${compareSelectionDro[idx]}</option>`);
    					if(isLastElement){
    					    $('#comparisionSelect').trigger('change');
    					    $('#totConValByDep').text(formatINR(currSelValu, 'INR'));
    					    $('#totConTxtByDep').text(currSelText);
    					}
    				});
    				loadDetailReportList(cdeptData, 'departmentDetailReport', '5');
    			}).on('loading.jstree', function (e, data) {
    			    document.getElementById('loadReport').style.visibility="visible";
    			}).on('loaded.jstree', function (e, data) {
    			    document.getElementById('loadReport').style.visibility="hidden";
    			}).on('ready.jstree', function (e, data) {
    			}).on('init.jstree', function (e, data) {
    			}).jstree({
    			  core: {
    				themes: {
    				  name: 'default'
    				}
    			  }
    			});
    		  }
    		let stringArray = (response.chartdata[1].split(','));
    		let numberArray = stringArray.map(Number);		  
    		  donutChartEl = document.querySelector('#typesChart'),
    		  donutChartConfig = {
    			  chart: {
    				height: 175,
    				width: 200,
    				parentHeightOffset: 0,
    				type: 'donut'
    			  },
    			  labels: response.chartdata[0].split(','),
    			  series: numberArray,
    			  stroke: {
    				show: false,
    				curve: 'smooth'
    			  },
                  tooltip: {
                    y: {
                        formatter: function (val) {
                          return formatINR(val);
                        }
                    }
                  },    			  
    			  dataLabels: {
    				enabled: false,
    				formatter: function (val, opts) {
    					return formatINR(opts.w.config.series[opts.seriesIndex], 'INR')
    				}
    			  },
    			  legend: {
    				show: false,
    				position: 'bottom',
    				markers: { offsetX: -3 },
    				itemMargin: {
    				  vertical: 3,
    				  horizontal: 10
    				},
    				labels: {
    				  colors: "black",
    				  show: true,
    				  useSeriesColors: false
    				},
                    formatter: function(seriesName, opts) {
                        const value = opts.w.globals.series[opts.seriesIndex];
                        return `${seriesName}: ${formatINR(value)}`;
                    }    				
    			  },
    			  plotOptions: {
    				pie: {
    				  donut: {
    					labels: {
    					  show: false,
    					  name: {
    						fontSize: '12.5px',
    					  },
    					  value: {
    						fontSize: '15px',
    						color: "black"
    					  },
    					  total: {
    						show: false,
    						fontSize: '1.5rem',
    						color: "black",
    						label: 'Operational',
    						formatter: function (w) {
    						  return '42%';
    						}
    					  }
    					}
    				  }
    				}
    			  },
    			  responsive: [
    				{
    				  breakpoint: 992,
    				  options: {
    					chart: {
    					  height: 380
    					},
    					legend: {
    					  position: 'bottom',
    					  labels: {
    						colors: 'black',
    						useSeriesColors: true
    					  }
    					}
    				  }
    				},
    				{
    				  breakpoint: 576,
    				  options: {
    					chart: {
    					  height: 320
    					},
    					plotOptions: {
    					  pie: {
    						donut: {
    						  labels: {
    							show: true,
    							name: {
    							  fontSize: '1.5rem'
    							},
    							value: {
    							  fontSize: '1rem'
    							},
    							total: {
    							  fontSize: '1.5rem'
    							}
    						  }
    						}
    					  }
    					},
    					legend: {
    					  position: 'bottom',
    					  labels: {
    						colors: "black",
    						useSeriesColors: true
    					  }
    					}
    				  }
    				},
    				{
    				  breakpoint: 420,
    				  options: {
    					chart: {
    					  height: 280
    					},
    					legend: {
    					  show: false
    					}
    				  }
    				},
    				{
    				  breakpoint: 360,
    				  options: {
    					chart: {
    					  height: 250
    					},
    					legend: {
    					  show: false
    					}
    				  }
    				}
    			  ]
    			};
    		  if (typeof donutChartEl !== undefined && donutChartEl !== null) {
    			donutChart = new ApexCharts(donutChartEl, donutChartConfig);
    	 		donutChart.render();	
    		  }	 
    		},
    		beforeSend: function(){
    		    document.getElementById('load').style.visibility="visible";
    		},
            complete: function (data) {
              document.getElementById('load').style.visibility="hidden";
            },		
    		error: function (xhr, status, error) {
    			// Handle error
    			// alert('Form submission failed: ' + error);
    		}
    	});  
        }

          $('#locationFilterSel').select2({
              placeholder: "Choose Location", 
              allowClear: true,
              dropdownParent: $('#contractLocationSelector')
          });
          
          $(document).on('click', '#locationFilter', function(){
              _setCookie('filterByLocationReport', JSON.stringify($('#locationFilterSel').val()));
              window.location.reload();
          });
      
    }); 
    
    let donutChartJsTree = null;
    
    function renderDonutChart(parentLabel, parentValue, childrenData) {
      
      if (donutChartJsTree) donutChartJsTree.destroy();
    

        // Build chart labels and values
        const labels = [parentLabel, ...childrenData.map(c => c.label)];
        const values = [parentValue, ...childrenData.map(c => c.value)];
        
        const colors = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a', '#d4526e'];
        
        const options = {
        chart: {
            height: 175,
            width: 200,
            parentHeightOffset: 0,
            type: 'donut'
        },
        labels: labels,
        series: values,
        colors: colors,
        title: {
          text: `${parentLabel}`,
          align: 'center',
          style: { fontSize: '12px', color: '#333' }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                  return formatINR(val);
                }
            }
        },    			  
        dataLabels: {
            enabled: false,
            formatter: function (val, opts) {
            	return formatINR(opts.w.config.series[opts.seriesIndex], 'INR')
            }
        },
        legend: {
          show: false, // <-- hide legends here
          position: 'bottom',
          labels: { colors: '#444', useSeriesColors: false }
        },
        plotOptions: {
          pie: {
            donut: {
              size: '70%',
              labels: {
                show: false,
                total: {
                  show: true,
                  label: 'Total',
                  formatter: () => {
                    const total = values.reduce((a, b) => a + b, 0);
                    return total.toLocaleString();
                  }
                }
              }
            }
          }
        }
        };
    
      donutChartJsTree = new ApexCharts(document.querySelector("#donutChartContractTree"), options);
      donutChartJsTree.render();
    }   

  // ================= Onclick Status action ====================//

  $(document).on("click", '#showAllCounts', function() {
    $('#contractDetailTree').jstree(true).refresh();
  });
  
  $(document).on("click", ".loadstatus", function () {
    let statusName = $(this).data('stat');
    loadStatusList(statusName);
  });

  //For Executed Status
  $(document).on("click", ".loadstatusexecuted", function () {
    let statusName = $(this).data('stat');
    loadStatusList(statusName);
  });

  //For Expired year
  $(document).on("click", ".loadyeardata", function () {
    let yearVal = $(this).data('year');
    _setCookie('filterByYear', yearVal);
    window.location.href = `${APP_URL}/contracts/reports-expired`;
  });

  //For Exception Report
  $(document).on("click", ".loadExceptionData", function () {
    let exceptType = $(this).data('cextype');
    loadExceptionList(exceptType);
  });

  //For Contracts Type Report
  $(document).on("click", ".showLocationTable", function () {

    let contType = $(this).data('ctype');
    let currLocation = $('#currentLocation').val() ?? 0;

    $('#contractTypeSelected').text($(this).data('ctypename'));

    loadLocationList(contType, currLocation);

  });

  //For Contracts Type Value Report
  $(document).on("click", ".showLocationValueTable", function () {
    let contType = $(this).data('ctype');
    $('#contractTypeSelected').text($(this).data('ctypename'));
    loadLocationValueList(contType);
  });
  
  //For Contracts Type Value Report
  $(document).on("click", ".count-toggle", function () {
    $('.count-toggle').removeClass('btn-warning active-toggle').addClass('btn-outline-secondary')
    $(this).removeClass('btn-outline-secondary').addClass('btn-warning active-toggle');
    let tabToggle = $(this).data('toggle-tb');
    toggleTabValueCount(tabToggle);
  });

  //For Contracts Type Value Report Available Toggles
  $(document).on("click", ".available-toggle", function () {
    $('.available-toggle').removeClass('btn-warning active-toggle').addClass('btn-outline-secondary')
    $(this).removeClass('btn-outline-secondary').addClass('btn-warning active-toggle');
    let tabToggle = $(this).data('toggle-tb');
    toggleTabAvailable(tabToggle);
  });

  //For Contracts Type Value Report Show Data
  $(document).on("click", ".showData", function () {
    scrollToElm('#departmentDetailReport');
  });

  //For Departments Report
  $(document).on("change", "#comparisionSelect", function () {
    var currChartvalu = $(this).find(':selected').val();
    var currChartText = $(this).find(':selected').val();
	let donutUpdateConfig = Object.assign({}, donutChartConfig);
	let currText = 'Total';
	
    if(currChartvalu != 0){
        var currChartText = $(this).find(':selected').data('chart-text');
        var currChartValu = $(this).find(':selected').data('chart-val');
    
    	let stringArray = currChartValu.split(',');
    	let numberArray = stringArray.map(Number);
    	donutUpdateConfig['labels'] = currChartText.split(',');
    	donutUpdateConfig['series'] = numberArray;
    	currText = donutUpdateConfig['labels'][0];
    }else{
        currChartvalu = $(this).find(':selected').data('all-val');
    }
    //Set Text and Value Titles
    $('#totConValByDep').text(formatINR(currChartvalu, 'INR'));
    $('#totConTxtByDep').text(currText);    
    
    donutChart.destroy(); 
    if (typeof donutChartEl !== undefined && donutChartEl !== null) {
        donutChart = new ApexCharts(donutChartEl, donutUpdateConfig);
        donutChart.render();	
    }
  });
  
  //For Contract Tags Report
  $(document).on("click", ".showLocationTagsTable", function () {
    let contType = $(this).data('ctype');
    $('#contractTypeSelected').text($(this).data('ctypename'));
    loadTagsLocationList(contType);
  });

  //For Departments Report
  $(document).on("click", ".loadDeptmentData", function () {
    let contDept = $(this).data('cdept');
    loadDepartmentList(contDept);
  });
 
 //For Location Change in Contract Types 
 $(document).on("change", "#locationChangeCtype", function () {

  let contType = $(this).val();

  //$('#contractTypeSelected').text($(this).data('ctypename'));

  window.location.href = APP_URL + '/contracts/reports-contract-types?locationId='+contType;

  //loadLocationList(0, contType);

 });  

  //For Print Report Html
  $(document).on("click", ".printableButton", function () {
    
    let allImages = [];
    loopLength = $('.SectionToPrint').length;
    for( let img of $('.SectionToPrint')){
        html2canvas(img).then((canvas) => {
          let dataURL = canvas.toDataURL("image/png");
          allImages.push(dataURL.replace(/^data:image\/?[A-z]*;base64,/,''));
          let url__ = _getCookie('exportUrl');
          let params__ = _getCookie('exportParams');
          loopLength--;
          triggerExport(url__,params__,allImages);
        });        
    }
    
  });
  
  function triggerExport(url_,params_,allImages_){
      if(loopLength == 0){
        $('[name="exportUrl"]').val(url_);
        $('[name="exportParams"]').val(params_);
        $('[name="imgs"]').val(JSON.stringify(allImages_));
        $('#createExport').submit();
      }
  }

  $('.nav-tabs a').on('show.bs.tab', function () {
    loadExpiredData(true, $(this).attr('id'));
  });
  
  
  // ================= Onclick Status action Ends ====================//


  // ================= Department Tree Starts ====================//

  if (departmentTree.length) {
    departmentTree.jstree({
      core: {
        themes: {
          name: 'default'
        }
      }
    });
  }
  // ================= Department Tree Ends ====================//
});

function toggleTabValueCount(tabToggle){
    if(tabToggle != 'all'){
        $('.toggle-spans').addClass('d-none');
        $(`.toggle_${tabToggle}`).removeClass('d-none');
    }else{
        $('.toggle-spans').removeClass('d-none');
    }
}

function toggleTabAvailable(tabToggle){
    if(tabToggle != 'all'){
        $('.no-count').addClass('d-none');
        $(`.${tabToggle}-count`).removeClass('d-none');
    }else{
        $('.all-count').removeClass('d-none');
    }
}
function loadStatusList(status=""){
  if (status == '') {
    $('.contracts-report').DataTable();
  } else {
    $('.contracts-report').DataTable().destroy();
    $('.contracts-report tbody').empty();    
    $('.contracts-report').DataTable({
      ajax: {
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: APP_URL + '/contracts/reports-status-data', type: 'POST',
        data: { 'status': status },
      },      
      "ordering": false,
      deferLoading: 57,
      pageLength: 10,
      "bPaginate": true,
      "bInfo" : true, 
      processing: true,
        "initComplete": function() {
            setExportParams('reports-status-data', JSON.stringify({ 'status': status }));
        },       
      columns: [
        {
          data: 'id', render: function (data, type, row, meta) {
            var rowIndex = meta.row + 1; // Adding 1 to start index from 1 instead of 0
            return rowIndex;
          }
        },
        { data: 'contract_name' },
        { data: 'location_branch' },
        { data: 'contract_type' },
        { data: 'fixed_date' },
        { data: 'contract_end_date' },
        { data: 'currency_value_converted' },
      ],
      'columnDefs': [
        {
          className: 'control',
          orderable: false,
          targets: 0,
          searchable: false
        },
        {
          "targets": 1,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Contract Name');
          },
          "render": function (data, type, row, meta) {

            if (type === 'display') {
              return '<a href="' + APP_URL + '/contracts/' + row['id'] + '" class="custom-ta">' + data +
                '</a>';
            } else {
              return data; // Return the original data for filtering
            }
          }
        },
        {
          'targets': 2,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Location');
          }
        },
        {
          'targets': 3,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Contract Type');
          }
        },
        {
          'targets': 4,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Fixed Date');
          }
        },
        {
          'targets': 5,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'End Date');
          }
        },
        {
          'targets': 6,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Contract Value');
          }
        }

      ],
      destroy: true,
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['contract_name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
  }
}

function loadExpiredData(reload = false, status = 'expired') {
  if ($('.contracts-report-expired').length) {
    if (reload) {
      $('.contracts-report-expired').DataTable().destroy();
      $('.contracts-report-expired tbody').empty();
      dataTableLoadExpire(status);
    } else {
      dataTableLoadExpire(status);
    }
  }
}

function dataTableLoadExpire(status) {
  if (status == '') {
    $('.contracts-report-expired').DataTable();
  } else {
    $('.contracts-report-expired').DataTable({
      "initComplete": function () {
        //_deleteCookie('filterByStatus');
      },
      ajax: {
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: APP_URL + '/contracts/reports-expired-data', type: 'POST',
        data: { 'yearFilter': _getCookie('filterByYear') ?? currYear, 'filterStatus': status },
      },
      "ordering": false,
      deferLoading: 57,
      pageLength: 10,
      processing: true,
      "initComplete": function() {
        setExportParams('reports-expired-data', JSON.stringify({ 'yearFilter': _getCookie('filterByYear') ?? currYear, 'filterStatus': status }));
      },        
      columns: [
        {
          data: 'id', render: function (data, type, row, meta) {
            var rowIndex = meta.row + 1; // Adding 1 to start index from 1 instead of 0
            return rowIndex;
          }
        },
        { data: 'contract_name' },
        { data: 'location_branch' },
        { data: 'contract_type' },
        { data: 'fixed_date' },
        { data: 'contract_end_date' },
        { data: 'currency_value_converted' },
      ],
      'columnDefs': [
        {
          'targets': 0,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('scope', 'row');
            $(td).attr('data-label', 'ID');
          }
        },
        {
          "targets": 1,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Contract Name');
          },
          "render": function (data, type, row, meta) {

            if (type === 'display') {
              return '<a href="' + APP_URL + '/contracts/' + row['id'] + '" class="custom-ta">' + data +
                '</a>';
            } else {
              return data; // Return the original data for filtering
            }
          }
        },
        {
          'targets': 2,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Location');
          }
        },
        {
          'targets': 3,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Contract Type');
          }
        },
        {
          'targets': 4,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Fixed Date');
          }
        },
        {
          'targets': 5,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'End Date');
          }
        },
        {
          'targets': 6,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Contract Value');
          }
        }        
      ],
      orderCellsTop: true
    });
  }
}

function loadLocationList(ctype = 0, loc = 0) {
if($('#locationTable').length > 0){
  $('#locationTable').DataTable().destroy();
  $('#locationTable tbody').empty();
  $('#locationTable').DataTable({
    ajax: {
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      url: APP_URL + '/contracts/reports-location-data', type: 'POST',
      data: { 'contractType': ctype, 'locationId': loc },
    },
    deferLoading: 10,
    pageLength: 10,
    processing: true,
    "bPaginate": true,
    "bInfo" : true,
    language: { search: "" },
    columns: [
      { data: 'locName' },
      { data: 'locCount' }
    ],
    columnDefs: [
      {
        'targets': 0,
        'createdCell': function (td, cellData, rowData, row, col) {
          $(td).attr('data-label', 'ID');
          $(td).attr('class', 'fs-6');
        },
        "orderable": false,
      },      
      {
        'targets': 1,
        'createdCell':  function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Location Count'); 
            $(td).attr('class', 'text-center'); 
        },
        "render": function (data, type, row, meta) {

              if (type === 'display') {
                  if(data == 0){
                      return '<i class="ti ti-circle-x text-danger ti-md"></i>';
                  }else{
                      return '<div class="btn btn-icon btn-sm rounded-pill btn-success">' + data + '</div>';
                  }
              } else {
                  return data;
              }
        }
      }
    ]
  });
}
}

function loadTagsLocationList(ctype=0) {

  if($('#locationTagsTable').length > 0){
      $('#locationTagsTable').DataTable().destroy();
      $('#locationTagsTable tbody').empty();
      $('#locationTagsTable').DataTable({
        ajax: {
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          url: APP_URL + '/contracts/reports-location-tags-data', type: 'POST',
          data: { 'contractType': ctype },
        },
        deferLoading: 10,
        pageLength: 10,
        processing: true,
        "bPaginate": true,
        "bInfo" : true,
        language: { search: "" },
        columns: [
          { data: 'locName' },
          { data: 'locCount' }
        ],
        columnDefs: [
          {
            'targets': 0,
            'createdCell': function (td, cellData, rowData, row, col) {
              $(td).attr('data-label', 'ID');
              $(td).attr('class', 'fs-6');
            },
            "orderable": false,
          },      
          {
            'targets': 1,
            'createdCell':  function (td, cellData, rowData, row, col) {
                $(td).attr('data-label', 'Location Count'); 
                $(td).attr('class', 'text-center'); 
            },
            "render": function (data, type, row, meta) {
    
                  if (type === 'display') {
                      if(data == 0){
                          return '<i class="ti ti-circle-x text-danger ti-md"></i>';
                      }else{
                          return '<div class="btn btn-icon btn-sm rounded-pill btn-success">' + data + '</div>';
                      }
                  } else {
                      return data;
                  }
            }
          }
        ]
      });
  }
}

function loadLocationValueList(ctype=0) {
if($('#locationValueTable').length > 0){
  $('#locationValueTable').DataTable().destroy();
  $('#locationValueTable tbody').empty();
//   $('#locationValueTable').DataTable({
//     ajax: {
//       headers: {
//         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//       },
//       url: APP_URL + '/contracts/reports-location-value-data', type: 'POST',
//       data: { 'contractType': ctype },
//     },
//     deferLoading: 10,
//     pageLength: 10,
//     processing: true,
//     "bPaginate": true,
//     "bInfo" : true,
//     language: { search: "" },
//     columns: [
//       { data: 'locName' },
//       { data: 'locCount' }
//     ],
//     columnDefs: [
//       {
//         'targets': 0,
//         'createdCell': function (td, cellData, rowData, row, col) {
//           $(td).attr('data-label', 'ID');
//           $(td).attr('class', 'fs-6');
//         },
//         "orderable": false,
//       },      
//       {
//         'targets': 1,
//         'createdCell':  function (td, cellData, rowData, row, col) {
//             $(td).attr('data-label', 'Location Count'); 
//             $(td).attr('class', 'text-end'); 
//         },
//         "render": function (data, type, row, meta) {

//               if (type === 'display') {
//                   if(data == 0){
//                       return '<i class="ti ti-circle-x text-danger ti-md"></i>';
//                   }else{
//                       return '<div class="btn btn-sm btn-success text-end">' + data + '</div>';
//                   }
//               } else {
//                   return data;
//               }
//         }
//       }
//     ]
//   });
// if (contractValueTree.length) {
//   contractValueTree.jstree({
//       core: {
//         themes: {
//           name: 'default'
//         }
//       }
//     });
// }
}
}

function loadExceptionList(etype="") {

  if(etype == ""){
    $('#exceptionTable').DataTable();
  }else{
    $('#exceptionTable').DataTable().destroy();
    $('#exceptionTable tbody').empty();
    $('#exceptionTable').DataTable({
      ajax: {
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: APP_URL + '/contracts/reports-exceptions-data', type: 'POST',
        data: { 'exceptType': etype },
      },
      deferLoading: 10,
      pageLength: 10,
      processing: true,
      "initComplete": function() {
        setExportParams('reports-exceptions-data', JSON.stringify({ 'exceptType': etype }));
      },      
      "bPaginate": true,
      "bInfo" : true,
      language: { search: "" },
      columns: [
        {
          data: 'exceptdetails', render: function (data, type, row, meta) {
            var rowIndex = meta.row + 1; // Adding 1 to start index from 1 instead of 0
            return rowIndex;
          }
        },      
        { data: 'curconid' },
        { data: 'oldconid' },
        { data: 'exceptdetails' }
      ],
      columnDefs: [
        {
          'targets': 0,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'ID');
          },
          "orderable": false,
        },      
        {
          'targets': 1,
          'createdCell':  function (td, cellData, rowData, row, col) {
              $(td).attr('data-label', 'Contract');
          },
          "render": function (data, type, row, meta) {
            if (type === 'display') {
              return '<a href="' + APP_URL + '/contracts/' + data.id + '" class="custom-ta">' + data.contract_unique_id +
                '</a>';
            } else {
              return data; // Return the original data for filtering
            }
          }
        },
        {
          'targets': 2,
          'createdCell':  function (td, cellData, rowData, row, col) {
              $(td).attr('data-label', 'Prev Contract'); 
          },
          "render": function (data, type, row, meta) {
            if (type === 'display') {
                if(data.id){
                    return '<a href="' + APP_URL + '/contracts/' + data.id + '" class="custom-ta">' + data.contract_unique_id +'</a>';
                }else{
                    return 'NA';
                }
            } else {
              return data; // Return the original data for filtering
            }
          }
        }
      ]
    });
  }
}


function loadDetailReportList(dept="", elem='departmentReport', topz=0){
  let departmentReport = $(`#${elem}`);
  if (dept == '') {
    departmentReport.DataTable();
  } else {
    departmentReport.DataTable().destroy();
    departmentReport.find('tbody').empty();    
    departmentReport.DataTable({
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],    
        ajax: {
            headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: APP_URL + '/contracts/reports-contract-detail-data', type: 'POST',
            data: dept,
        },
        "initComplete": function() {
            //scrollToElm('#departmentDetailReport');
            setExportParams('reports-contract-detail-data', JSON.stringify(dept));
        },      
        "ordering": false,
        //deferLoading: 57,
        pageLength: 10,
        processing: true,
        columns: [
            {
              data: 'id', render: function (data, type, row, meta) {
                var rowIndex = meta.row + 1; // Adding 1 to start index from 1 instead of 0
                return rowIndex;
              }
            },
            { data: 'contract_name' },
            { data: 'location_branch' },
            { data: 'contract_type' },
            { data: 'fixed_date' },
            { data: 'contract_end_date' },
            { data: 'currency_value_converted' },
            { data: 'substatus' }
          ],
        'columnDefs': [
            {
              'targets': 0,
              'createdCell': function (td, cellData, rowData, row, col) {
                $(td).attr('scope', 'row');
                $(td).attr('data-label', 'ID');
              }
            },
            {
              "targets": 1,
              'createdCell': function (td, cellData, rowData, row, col) {
                $(td).attr('data-label', 'Contract Name');
              },
              "render": function (data, type, row, meta) {
    
                if (type === 'display') {
                  return '<a href="' + APP_URL + '/contracts/' + row['id'] + '" class="custom-ta">' + data +
                    '</a>';
                } else {
                  return data; // Return the original data for filtering
                }
              }
            },
            {
              'targets': 2,
              'createdCell': function (td, cellData, rowData, row, col) {
                $(td).attr('data-label', 'Location');
              }
            },
            {
              'targets': 3,
              'createdCell': function (td, cellData, rowData, row, col) {
                $(td).attr('data-label', 'Contract Type');
              }
            },
            {
              'targets': 4,
              'createdCell': function (td, cellData, rowData, row, col) {
                $(td).attr('data-label', 'Fixed Date');
              }
            },
            {
              'targets': 5,
              'createdCell': function (td, cellData, rowData, row, col) {
                $(td).attr('data-label', 'End Date');
              }
            },
            {
              'targets': 6,
              'createdCell': function (td, cellData, rowData, row, col) {
                $(td).attr('data-label', 'Contract Value');
              }
            },
    {
                  'targets': 7,
                  'createdCell':  function (td, cellData, rowData, row, col) {
                     $(td).attr('data-label', 'Status'); 
                  },
                    "render": function (data, type, row, meta) {
                        
                        // console.log(data);
    
                        if (type === 'display') {
                            if(data == 'completed'){
                                return '<div class="status-completed substatusText" data-count-id="status_executed_completed" data-count-exe="1">' + data + '</div>';
                            }else if(data == 'active'){
                                return '<div class="status-active substatusText" data-count-id="status_executed_active" data-count-exe="1">' + data + '</div>';
                            }else if(data == 'expired'){
                                return '<div class="status-expired substatusText" data-count-id="status_executed_expired" data-count-exe="1">' + data + '</div>';
                            }else if(data == 'Terminated'){
                                return '<div class="status-terminate substatusText" data-count-id="status_executed_terminated" data-count-exe="1">' + data + '</div>';
                            }else if(data == 'renewed'){
                                return '<div class="status-renewed substatusText" data-count-id="status_executed_renewed" data-count-exe="1">' + data + '</div>';
                            }else if(row.contract_status == 'Negotiation'){
                                return '<div class="status-negotiation substatusText" data-count-id="status_negotiation" data-count-exe="0">' + row.contract_status + '</div>';
                            }
                            else if(data == 'Initial Draft'){
                                return '<div class="status-initialdraft substatusText" data-count-id="status_'+ (row.contract_status).toLowerCase() +'" data-count-exe="0">' + row.contract_status + '</div>';
                            }  else if(data.toLowerCase() == 'under process'){
                                return '<div class="status-renewed substatusText" data-count-id="status_review" data-count-exe="0">' + data + '</div>';
                            }else {
                                return '<div class="status-renewed substatusText" data-count-id="status_executed_renewed" data-count-exe="1">' + data + '</div>';
                            }
                            
                            // return '<a href="' + base_url+'contracts/' + row['id'] + '" class="custom-tag">' + data +
                            //     '</a>';
                        } else {
                            return data; // Return the original data for filtering
                        }
                    },
                  "orderable": false
               }
    
          ],
        //orderCellsTop: true
    });
  }
}

function loadDepartmentList(dept="", elem='departmentReport'){
  let departmentReport = $(`#${elem}`);
  if (dept == '') {
    departmentReport.DataTable();
  } else {
    departmentReport.DataTable().destroy();
    departmentReport.find('tbody').empty();    
    departmentReport.DataTable({
      ajax: {
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: APP_URL + '/contracts/reports-contract-depts-data', type: 'POST',
        data: { 'deptId': dept },
      },      
      "ordering": false,
      deferLoading: 10,
      pageLength: 10,
      processing: true,
      "initComplete": function() {
        setExportParams('reports-contract-depts-data', JSON.stringify({ 'deptId': dept }));
      },      
      columns: [
        {
          data: 'id', render: function (data, type, row, meta) {
            var rowIndex = meta.row + 1; // Adding 1 to start index from 1 instead of 0
            return rowIndex;
          }
        },
        { data: 'contract_name' },
        { data: 'location_branch' },
        { data: 'contract_type' },
        { data: 'fixed_date' },
        { data: 'contract_end_date' },
        { data: 'currency_value_converted' }
      ],
      'columnDefs': [
        {
          'targets': 0,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('scope', 'row');
            $(td).attr('data-label', 'ID');
          }
        },
        {
          "targets": 1,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Contract Name');
          },
          "render": function (data, type, row, meta) {

            if (type === 'display') {
              return '<a href="' + APP_URL + '/contracts/' + row['id'] + '" class="custom-ta">' + data +
                '</a>';
            } else {
              return data; // Return the original data for filtering
            }
          }
        },
        {
          'targets': 2,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Location');
          }
        },
        {
          'targets': 3,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Contract Type');
          }
        },
        {
          'targets': 4,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Fixed Date');
          }
        },
        {
          'targets': 5,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'End Date');
          }
        },
        {
          'targets': 6,
          'createdCell': function (td, cellData, rowData, row, col) {
            $(td).attr('data-label', 'Value');
          }
        }

      ],
      orderCellsTop: true
    });
  }
}

function loadClausesContractList(dept="", elem='clausesListContractsTable'){
  let departmentReport = $(`#${elem}`);
  if (dept == '') {
    departmentReport.DataTable();
  } else {
    departmentReport.DataTable().destroy();
    departmentReport.find('tbody').empty();    
    departmentReport.DataTable({
    //   ajax: {
    //     headers: {
    //       'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //     },
    //     url: APP_URL + '/contracts/reports-contract-depts-data', type: 'POST',
    //     data: { 'deptId': dept },
    //   },      
      "ordering": false,
      deferLoading: 10,
      pageLength: 10,
      processing: true
    });
  }
}

function setExportParams(url="", params={}){
    _setCookie('exportUrl', url);
    _setCookie('exportParams', params);
}

function _setCookie(name, value, daysToExpire, path = '/', domain = '') {
  const cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}`

  let expires = ''
  if (daysToExpire) {
    const expirationDate = new Date()
    expirationDate.setTime(expirationDate.getTime() + daysToExpire * 24 * 60 * 60 * 1000)
    expires = `; expires=${expirationDate.toUTCString()}`
  }

  const pathString = `; path=${path}`
  const domainString = domain ? `; domain=${domain}` : ''

  document.cookie = `${cookie}${expires}${pathString}${domainString}`
}

function _getCookie(name) {
  const cookies = document.cookie.split('; ')

  for (let i = 0; i < cookies.length; i++) {
    const [cookieName, cookieValue] = cookies[i].split('=')
    if (decodeURIComponent(cookieName) === name) {
      return decodeURIComponent(cookieValue)
    }
  }

  return null
}

function _checkCookie(name) {
  return this._getCookie(name) !== null
}

function _deleteCookie(name) {
  document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
}


function printSection(elem="SectionToPrint")
{
    var mywindow = window.open('', 'PRINT', 'height=768,width=1024');

    mywindow.document.write('<html><head><title>' + document.title  + '</title>');
    mywindow.document.write('</head><body >');
    mywindow.document.write('<h1>' + document.title  + '</h1>');
    mywindow.document.write(document.getElementById(elem).innerHTML);
    mywindow.document.write('</body></html>');

    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10*/

    mywindow.print();
    mywindow.close();

    return true;
}

function scrollToElm(targetElm){
    $(targetElm)[0].scrollIntoView({
        behavior: "smooth",
        block: "start"
    });      
}

function formatINR(amount, cur = '₹') {
    let INR = parseFloat(amount);
    let ext = "";

    const INR_THOUSAND = 1000;
    const INR_LAKH = 100 * INR_THOUSAND;   // 1,00,000
    const INR_CRORE = 100 * INR_LAKH;      // 1,00,00,000

    if (amount >= INR_CRORE) {
        INR = amount / INR_CRORE;
        ext = "Cr";
        INR = INR.toFixed(2) + ' ' + ext;
    } else if (amount >= INR_LAKH) {
        INR = amount / INR_LAKH;
        ext = (INR === 1) ? "Lakh" : "Lakhs";
        INR = INR.toFixed(2) + ' ' + ext;
    } else if (amount >= INR_THOUSAND) {
        INR = amount / INR_THOUSAND;
        ext = "K";
        INR = INR.toFixed(2) + ' ' + ext;
    } else {
        if(amount > 0){
            INR = amount.toFixed(2);
        }else{
            INR += ".00";
        }
    }

    return `${cur} ${INR}`;
}