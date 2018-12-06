module.exports = class {

	onDataLoad(props) {
		return {
			accidentDetail: this.dataQL('accidentDetail',{
				'rpaid':props.rpaid
			}),
			accident:[],
		};
	}

	onLoaded() {
		console.log('加载数据。。。');
		this.state.accidentDetail.fetch((json) => {
			console.log(JSON.stringify(json));
			if (json) {
			this.setState({
				accident: json.accident,
			})
		}
	});


	}

	goHistory() {
		window.history.go(-1);
	}


}