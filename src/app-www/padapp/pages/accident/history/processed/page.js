module.exports = class {

	onDataLoad() {
		return {
			accidentHistoryProcessed: this.fetchQL('accidentHistoryProcessed'),
			show:true
		};
	}

	nextPage() {
		this.state.accidentHistoryProcessed.fetchNextPage();
	}

	onLoaded() {
		if(this.state.accidentHistoryProcessed.data.list.length == 0) {
			this.setState({
				show: false,
			});
		}
	}

	goHistory() {
		window.history.go(-1);
	}



}