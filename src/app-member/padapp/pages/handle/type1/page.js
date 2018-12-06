module.exports = class {

	onDataLoad(props) {
		return {
			rpaId: props.rpaId,
			type: props.type,
			handleCollection: this.dataQL('handleCollection', {
				rpaId: props.rpaId,
			}),
			handleAccident: this.fetchQL('handleAccident', {
				rpaId: props.rpaId,
			}),
		};
	}

	goCollection() {
		padapp.navigate.linkTo({
			url: '/handle.shot5',
			params: {
				rpaId: this.state.rpaId,
				type: this.state.type,
			}
		})
	}

	goShot2() {
		padapp.navigate.linkTo({
			url: '/handle.duty',
			params: {
				rpaId: this.state.rpaId,
				type: this.state.type,
			}
		})
	}

}