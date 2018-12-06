module.exports = class {

	onDataLoad(props) {
		return {
			show: true,
			data: props.data ? props.data : [],
			img: true,
		};
	}

	onLoaded() {
		let data = this.state.data;
		if (data.atPictureAddress === undefined) {
			this.setState({
				img: false,
			})
		}
	}

	changeShow() {
		this.setState({
			show: !this.state.show,
		})
	}

	goBack() {
		padapp.navigate.pop();
	}

}