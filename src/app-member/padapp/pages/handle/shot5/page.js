module.exports = class {

	onDataLoad(props) {
		return {
			rpaId: props.rpaId,
			type: props.type,
			handleCollection: this.fetchQL('handleCollection', {
				rpaId: props.rpaId,
			}),
			handleAccident: this.fetchQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			per: 60,
			rpaProcessState: 31,
			stateName: '等待定责',
			time: '00:00',
			time1: '00:00',
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
		let stateName = that.state.stateName;
		let time = that.state.time;
		let time1 = that.state.time1;
		var perInterval = that.setInterval(() => {
			let per = this.state.per;
			if (per > 0) {
				per--;
				that.setState({
					per: per,
				})
			} else {
				that.setState({
					per: 60,
				})
				// that.clear(perInterval);
			}
		}, 1000)
		that.state.handleAccident.fetch((flag) => {
			let rpaProcessState = parseInt(flag.accident.rpaProcessState);
			if (rpaProcessState === 31) {
				let loop = that.setInterval(() => {
					that.state.handleAccident.fetch((json) => {
						let loopRpaProcessState = parseInt(json.accident.rpaProcessState);
						if (loopRpaProcessState !== 31) {
							let rpaCaseTime = json.accident.rpaCaseTime;
							let date = new Date(rpaCaseTime);
							if (loopRpaProcessState === 32) {
								stateName = '正在定责';
								time = date.getHours() + ':' + date.getMinutes();
							}
							if (loopRpaProcessState === 33) {
								stateName = '定责完成';
								time1 = date.getHours() + ':' + date.getMinutes();
								that.setState({
									per: 0,
								});
								that.clear(loop);
								that.clear(perInterval);
							}
							this.setState({
								rpaProcessState: loopRpaProcessState,
								stateName: stateName,
								time: time,
								time1: time1,
							});
						}
					})
				}, 10000);
			} else {
				let date = new Date(flag.accident.rpaCaseTime);
				if (rpaProcessState === 32) {
					stateName = '正在定责';
					time = date.getHours() + ':' + date.getMinutes();
				}
				if (rpaProcessState === 33) {
					stateName = '定责完成';
					time1 = date.getHours() + ':' + date.getMinutes();
				}
				this.setState({
					per: 0,
					rpaProcessState: rpaProcessState,
					stateName: stateName,
					time: time,
					time1: time1,
				});
			}
		})
	}

	goBack() {
		window.history.go(-1);
	}

	goShot3() {
		let rpaProcessState = this.state.rpaProcessState;
		if (rpaProcessState > 32) {
			padapp.navigate.linkTo({
				url: '/handle.detail',
				params: {
					rpaId: this.state.rpaId,
					type: this.state.type,
				}
			})
		}
	}

}