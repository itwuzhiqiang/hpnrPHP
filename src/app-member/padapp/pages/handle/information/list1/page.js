module.exports = class {

	onDataLoad(props) {
		return {
			handleQueryAll: this.fetchQL('handleQueryAll'),
		};
	}

	onLoaded() {
		console.log(789)
	}


	goBack() {
		padapp.navigate.pop();
	}

	goChoose(item) {
		padapp.app.emit('/handle.queryAll.list', {
			data: item,
		});
		padapp.navigate.pop();
	}

}