module.exports = class {

	onDataLoad(props) {
		return {
			handlePlateType: this.fetchQL('handlePlateType'),
		};
	}

	onLoaded() {
		console.log(789)
	}


	goBack() {
		padapp.navigate.pop();
	}

	goChoose(item) {
		padapp.app.emit('/user.clmsg.list', {
			data: item,
		});
		padapp.navigate.pop();
	}

}