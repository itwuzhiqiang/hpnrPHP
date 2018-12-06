module.exports = class {

	onDataLoad(props) {
		return {
			accidentDetail: this.dataQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			parties:[],
			partyoneShow:false,
			partytwoShow:false,
			partythreeShow:false,
			partyone:[],
			partytwo:[],
			partythree:[],
			hostName:'http://test.fastepay.cn/',
		};
	}

	onLoaded() {
		console.log('加载数据。。。');
		this.state.accidentDetail.fetch((json) => {
			console.log(JSON.stringify(json));
			if (json) {
				this.setState({
					parties: json.parties,
				});
				if(json.parties.length>=1){
					this.setState({
						partyone: json.parties[0],
						partyoneShow:true,
					});
				}
				if(json.parties.length>=2){
					this.setState({
						partytwo: json.parties[1],
						partytwoShow:true,
					});
				}
				if(json.parties.length>=3){
					this.setState({
						partythree: json.parties[2],
						partythreeShow:true,
					});
				}

			}
		});


	}

	goHistory() {
		window.history.go(-1);
	}


}