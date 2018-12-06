module.exports = class {

	onDataLoad(props) {
		return {
			handleAccident: this.fetchQL('handleAccident', {
				rpaId: props.rpaId,
			}),
		};
	}

}