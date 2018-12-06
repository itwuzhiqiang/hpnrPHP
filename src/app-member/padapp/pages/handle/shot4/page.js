module.exports = class {

	onDataLoad(props) {
		return {
			handleAccident: this.dataQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			rpaId: props.rpaId,
			type: props.type,
			per: 0,
			stateName: '审核中',
			rpaProcessState: 13,
			userInfo: this.fetchQL('userInfo'),
		};
	}

	onLoaded() {

		let code = this.state.userInfo.data.code;
		if (code === -1) {
			padapp.navigate.linkTo({
				url: '/login',
				params: {
					popup: true,
				}
			})
		}

		let that = this;
		that.state.handleAccident.fetch((data) => {
			let rpaProcessState = parseInt(data.accident.rpaProcessState);
			if (rpaProcessState === 13 || rpaProcessState === 16) {
				let loop = that.setInterval(() => {
					that.state.handleAccident.fetch((json) => {
						let loopRpaProcessState = parseInt(json.accident.rpaProcessState);
						if (loopRpaProcessState === 14 || loopRpaProcessState === 15) {
							let stateName = that.state.stateName;
							if (loopRpaProcessState === 15) {
								stateName = '审核完成';
							}
							if (loopRpaProcessState === 14) {
								stateName = '重新拍照';
								that.overlay.msg(json.accident.rpaAuditMsg);
							}
							this.setState({
								per: 100,
								rpaProcessState: loopRpaProcessState,
								stateName: stateName,
							});

							that.clear(loop);
						}
					})
				}, 10000);
			} else {
				let stateName = that.state.stateName;
				if (rpaProcessState === 15) {
					stateName = '审核完成';
				}
				if (rpaProcessState === 14) {
					stateName = '重新拍照';
				}
				this.setState({
					per: 100,
					rpaProcessState: rpaProcessState,
					stateName: stateName,
				});
			}
		});


		let perInterval = setInterval(() => {
			let per = this.state.per;
			if (per < 99) {
				per++;
				that.setState({
					per: per,
				})
			} else {
				clearInterval(perInterval);
			}
		}, 1000);
	}

	goBack() {
		window.location.href = document.referrer;
	}

	goNext() {
		let rpaProcessState = this.state.rpaProcessState;
		if (rpaProcessState === 14) {
			this.goBack();
		} else if (rpaProcessState === 15) {
			padapp.navigate.linkTo({
				url: '/handle.remind2',
				params: {
					rpaId: this.state.rpaId,
					type: this.state.type,
				}
			})
		}
	}

}