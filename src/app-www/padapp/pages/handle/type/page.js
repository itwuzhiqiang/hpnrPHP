module.exports = class {

	onDataLoad(props) {
		return {
			handleReport: this.dataQL('handleReport', {
				points: props.points,
			}),
		};
	}

	goShot(type) {
		this.state.handleReport.setParam('type', type).fetch((json) => {
			if (json) {
				padapp.navigate.linkTo({
					url: '/handle.shot',
					params: {
						rpaId: json,
						type: type,
					}
				})
			}
		})
	}

	goShot1(type) {
		this.state.handleReport.setParam('type', type).fetch((json) => {
			if (json) {
				padapp.navigate.linkTo({
					url: '/handle.shot1',
					params: {
						rpaId: json,
						type: type,
					}
				})
			}
		})
	}

	goHistory() {
		window.history.go(-1);
	}
}