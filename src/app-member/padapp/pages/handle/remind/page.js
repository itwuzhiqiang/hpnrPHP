module.exports = class {

	onDataLoad() {
		return {
			show: false,
		};
	}

	setShow() {
		this.setState({
			show: !this.state.show,
		})
	}

	goRemind1() {
		padapp.navigate.linkTo({
			url: '/handle.remind1',
		})
	}

}