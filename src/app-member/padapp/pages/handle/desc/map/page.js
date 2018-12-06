module.exports = class {

	onDataLoad(props) {
		return {
			startPoint: JSON.parse(props.points),
		};
	}

	onLoaded() {
	}


	setAddree(e) {
		let str = '';
		if (e.address.street) {
			str = e.address.city + e.address.district + e.address.street + e.address.streetNumber;
		} else {
			str = e.address.city + e.address.district + e.address.business;
		}

		padapp.navigate.linkTo({
			url: '/handle.type',
			params: {
				points: JSON.stringify(e.point),
				rpaAddress: str,
			}
		})

	}

	goBack() {
		window.history.go(-1);
	}

}