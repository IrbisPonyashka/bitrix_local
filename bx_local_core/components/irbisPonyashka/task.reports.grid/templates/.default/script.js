
async function sendToApproval(rows) {
			
	let grid = BX.Main.gridManager.getById('reports_grid').instance; // Замените 'timesheets_grid' на ваш идентификатор грида
	let selectedRows = grid.getRows().getSelected();
	
	if(selectedRows){
		selectedRows.forEach( async row => {
			let rowId = row.getId();
			let selectedRowData = rows.find(rowItemData => {
				return rowItemData.id == rowId;  
			});

			if(selectedRowData){
				let requestData = {
					"update" : true,
					"data": {
						"filter" : {
							"START_DATE": selectedRowData.data.START_DATE,
							"END_DATE": selectedRowData.data.END_DATE,
							"EMPLOYEE_ID": selectedRowData.data.EMPLOYEE_ID,
						}
					}
				};

				let updateReports = await updateReportsByFilter(requestData);
				if(updateReports){
					updateReports = JSON.parse(updateReports);
					updateReports.success === true ? grid.editSelectedSave() : alert(updateReports);
				}
			}

		});
	}
	
	function getCreatedDateValue (filterFields)
	{
		var createdDateFrom = filterFields['CREATED_DATE_from'];
		var createdDateTo = filterFields['CREATED_DATE_to'];
		var createdDateDatesel = filterFields['CREATED_DATE_datesel'];

		// Инициализируем переменные для хранения диапазона дат
		let dateFrom = createdDateFrom ;
		let dateTo = createdDateTo ;


		if (createdDateDatesel && !dateFrom && !dateTo)
		{
			var dateRange = getDateRangeFromSelection(createdDateDatesel);
			dateFrom = dateRange[0];
			dateTo = dateRange[1];
			return [dateFrom.format('DD.MM.YYYY') , dateTo.format('DD.MM.YYYY') ]
		}else{
			return [createdDateFrom, createdDateTo ]
		}

	}

	async function prepareFieldsToSendReports(selectedRows)
	{	
		let dataArr = [];
		selectedRows.forEach( el => {
			let currRowData = rows.find(rowEl => {
				return rowEl.id == el.getId();  
			})

			dataArr.push({
				"TASK_ID" : currRowData.id, 
				"EMPLOYEE_ID" : BX.message('USER_ID'),
				"START_DATE" : createdDatePeriodValue[0],
				"END_DATE" : createdDatePeriodValue[1],
				"STATUS" : 0,
				"AGREED_TIME" : currRowData.data.AGREED_TIME ,
				"ELAPSED_TIME" :  currRowData.data.ELAPSED_TIME ,
			});
		});
		
		return dataArr;
	}

	function getDateRangeFromSelection(selection)
	{
		const now = moment();
		switch (selection) {
			case 'YESTERDAY':
				return [now.clone().subtract(1, 'days').startOf('day'), now.clone().subtract(1, 'days').endOf('day')];
			case 'CURRENT_DAY':
				return [now.clone().startOf('day'), now.clone().endOf('day')];
			case 'TOMORROW':
				return [now.clone().add(1, 'days').startOf('day'), now.clone().add(1, 'days').endOf('day')];
			case 'CURRENT_WEEK':
				return [now.clone().startOf('week'), now.clone().endOf('week')];
			case 'CURRENT_MONTH':
				return [now.clone().startOf('month'), now.clone().endOf('month')];
			case 'CURRENT_QUARTER':
				return [now.clone().startOf('quarter'), now.clone().endOf('quarter')];
			case 'LAST_7_DAYS':
				return [now.clone().subtract(7, 'days').startOf('day'), now.clone().endOf('day')];
			case 'LAST_30_DAYS':
				return [now.clone().subtract(30, 'days').startOf('day'), now.clone().endOf('day')];
			case 'LAST_60_DAYS':
				return [now.clone().subtract(60, 'days').startOf('day'), now.clone().endOf('day')];
			case 'LAST_90_DAYS':
				return [now.clone().subtract(90, 'days').startOf('day'), now.clone().endOf('day')];
			case 'LAST_WEEK':
				return [now.clone().subtract(1, 'weeks').startOf('week'), now.clone().subtract(1, 'weeks').endOf('week')];
			case 'LAST_MONTH':
				return [now.clone().subtract(1, 'months').startOf('month'), now.clone().subtract(1, 'months').endOf('month')];
			case 'NEXT_WEEK':
				return [now.clone().add(1, 'weeks').startOf('week'), now.clone().add(1, 'weeks').endOf('week')];
			case 'NEXT_MONTH':
				return [now.clone().add(1, 'months').startOf('month'), now.clone().add(1, 'months').endOf('month')];
			case 'YEAR':
				return [now.clone().add(0, 'years').startOf('year'), now.clone().add(0, 'years').endOf('year')];
			case 'MONTH':
				return [now.clone().add(0, 'months').startOf('month'), now.clone().add(0, 'months').endOf('month')];
			default:
				return [null, null];
		}
	}
	
	async function updateReportsByFilter(rowData)
	{
		return new Promise((resolve, reject) => {
			BX.ajax({
				url: '/local/components/micros/task.timesheets.grid/ajax.php?action=list&type=reports',
				data : JSON.stringify(rowData),
				method: "POST",
				dataType: "json", 
				processData: false,
				preparePost: false,
				onsuccess: function(data){
					resolve(data);
				},
				onfailure: function(data){
					reject(data)
				} 
			})
		})
	}
}