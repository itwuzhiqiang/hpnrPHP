module.exports = class {

	onDataLoad() {
		return {
			accidentHistoryCondition: this.dataQL('accidentHistoryCondition'),
			show:true,
			rpaid:0
		};
	}

	onLoaded() {
		this.addListener('/map.change', (data) => {
			this.state.accidentHistoryCondition
				.setParam('lontitude', data.point.lng)
				.setParam('latitude',data.point.lat)
				.setParam('rpaid',rpaid)
				.fetch((json) => {
					if(json) {
						padapp.navigate.linkTo({
							url: '/accident.history'
						})
					}
				});
		});
	}

	goMap() {
		padapp.navigate.push({
			url: '/accident/history/map'
		})
	}

	submitCondition(lontitude,latitude,rpaid){
		this.state.accidentHistoryCondition
			.setParam('lontitude', data.point.lng)
			.setParam('latitude',data.point.lat)
			.setParam('rpaid',rpaid)
			.fetch((json) => {
			if(json) {
				padapp.navigate.linkTo({
					url: '/accident.history'
				})
			}
		});
	}

	openMap(){
		this.overlay.msg('打开地图');
	}

	goHistory() {
		window.history.go(-1);
	}



}