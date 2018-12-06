module.exports = class {

	onDataLoad(props) {
		return {
			rpaAddress: props.rpaAddress,
			address: props.address,
			str: '&',
		};
	}

	onLoaded() {
	}

	setVal(e) {
		let val = e.target.value;
		this.setState({
			address: val,
		})
	}

	goSure() {
		let val = this.state.address;
		padapp.app.emit('/handle.duty.accident', {
			address: val,
		});
		padapp.navigate.pop()
	}

	goBack() {
		padapp.navigate.pop()
	}

}