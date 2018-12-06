module.exports = class {

	onDataLoad(props) {
		return {
			show: false,
			points: props.points == '{}' ? JSON.stringify({lng: 106.56198, lat: 29.568044}) : props.points,
		};
	}


	setShow() {
		this.setState({
			show: true,
		})
	}

	call() {
		window.location.href = 'tel:110';
	}

	goType() {
		padapp.navigate.linkTo({
			url: '/handle.desc.map',
			params: {
				points: this.state.points,
			}
		})
	}

	goBack() {
		window.history.go(-1);
	}

}