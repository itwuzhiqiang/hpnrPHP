module.exports = class {

	onDataLoad(props) {
		return {
			handleAccidentType: this.fetchQL('handleAccidentType'),
			rpaId: props.rpaId,
			type: props.type,
		};
	}

	onLoaded() {
	}

	goDetail(item) {
		padapp.navigate.push({
			url: '/handle/duty/detail',
			params: {
				data: item,
			}
		})
	}

	goBack() {
		padapp.navigate.pop();
	}

	goChoose(item) {
		padapp.app.emit('/handle.duty.accident', {
			data: item,
		});
		padapp.navigate.pop();
	}

}