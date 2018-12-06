module.exports = class {

	onDataLoad() {
		return {
			accidentHistoryGetAll: this.fetchQL('accidentHistoryGetAll'),
			accidentHistoryCondition: this.dataQL('accidentHistoryCondition'),
			show:true,
			rpaid:'',
		};
	}

	nextPage() {
		this.state.accidentHistoryGetAll.fetchNextPage();
	}

	onLoad() {
		this.addListener('/handle.desc.map', (data) => {
			this.state.accidentHistoryCondition
				.setParam('lontitude', data.data.point.lng)
				.setParam('latitude',data.data.point.lat)
				.setParam('rpaid',this.state.rpaid)
				.fetch((json) => {
					if(json) {
						this.state.accidentHistoryGetAll.reFetch();
					}
				});
		});
	}

	onLoaded() {
		if(this.state.accidentHistoryGetAll.data.list.length == 0) {
			this.setState({
				show: false,
			});
		};
	}

	openMap(rpaid){
		this.setState({
			rpaid: rpaid,
		});
		padapp.navigate.push({
			url: '/accident/history/map',
			params:{
				rpaid:rpaid
			}
		})
	}

	goProcessed(){
		padapp.navigate.linkTo({
			url: '/accident.history.processed'
		})
	}

	goHistory() {
		window.history.go(-1);
	}



}