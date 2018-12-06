module.exports = class {

	onDataLoad(props) {
		console.log(props.points);
		return {
			points: props.points == '{}' ? JSON.stringify({lng: 106.557220, lat: 29.570656}) : props.points,
		};
	}

	goChooseType() {
		console.log(this.state.points);
		padapp.navigate.linkTo({
			url: '/handle.type',
			params: {
				points: this.state.points,
			}
		})
	}

	goHistory() {
		window.history.go(-1);
	}
}