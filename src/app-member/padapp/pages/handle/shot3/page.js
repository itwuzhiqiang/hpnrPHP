module.exports = class {

	onDataLoad(props) {
		return {
			rpaId: props.rpaId,
			type: props.type,
			handleAccident: this.fetchQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			handleAccidentTypeDetails: this.dataQL('handleAccidentTypeDetails'),
			handleAccidentType: this.fetchQL('handleAccidentType'),
			accidentType: ['其它', '无责', '次责', '同责', '主责', '全责'],
			atName: '',
			atStatute: '',
			atRegulations: '',
			atPictureAddress: '',
			arr: [],
		};
	}

	onLoaded() {
		let atId = this.state.handleAccident.data.accident.rpaManger;
		this.state.handleAccidentTypeDetails.setParam('atId', atId).fetch((data) => {
			this.setState({
				atName: data.data.atName,
				atStatute: data.data.atStatute,
				atRegulations: data.data.atRegulations,
				atPictureAddress: data.data.atPictureAddress,
			})
		});
		let arr = [];
		let accidentType = this.state.accidentType;

		let parties = this.state.handleAccident.data.parties;
		_.map(parties, (item) => {
			arr.push({
				name: item.apPartiesName,
				type: item.apResponsibilityType,
				typeName: accidentType[item.apResponsibilityType],
				text: item.apResponsibilityText,
			})
		});
		arr.sort((a, b) => {
			return b.type - a.type;
		});
		this.setState({
			arr: arr,
		})
	}

	goBack() {
		window.history.go(-1);
	}

	goDetail() {
		padapp.navigate.linkTo({
			url: '/handle.detail',
			params: {
				rpaId: this.state.rpaId,
				type: this.state.type,
			}
		})
	}



}