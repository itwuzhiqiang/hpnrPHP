module.exports = class {

	onDataLoad(props) {
		return {
			handleAccident: this.fetchQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			handleSendToParty: this.dataQL('handleSendToParty', {
				rpaId: props.rpaId,
			}),
			handleSend: this.dataQL('handleSend', {
				rpaId: props.rpaId,
			}),
			handleVerificAndPhone: this.dataQL('handleVerificAndPhone'),
			handleRefused: this.dataQL('handleRefused', {
				rpaId: props.rpaId,
			}),
			handleComfirm: this.dataQL('handleComfirm', {
				rpaId: props.rpaId,
			}),
			handlePartyComfirm: this.dataQL('handlePartyComfirm', {
				rpaId: props.rpaId,
			}),
			type: 1,
			show: false,
			show1: false,
			time: [],
			partiesShow: [],
			verific: [],
			isChoice: [],
			ac_apId: '',
			phone: '',
			apInputOrder: '',
			number: 0,
			userInfo: this.fetchQL('userInfo'),
		};
	}

	onLoaded() {
		let code = this.state.userInfo.data.code;
		if (code === -1) {
			this.overlay.msg('登录失效');
			padapp.navigate.linkTo({
				url: '/login',
				params: {
					popup: true,
				}
			})
		}

		$('.appSence').css('position','');
		// $('.codeinput').on('focus', function () {
		// 	var __this = this;
		// 	setTimeout(function () {
		// 		__this.scrollIntoViewIfNeeded();
		// 	}, 0)
		// });
		let arr = this.state.time;
		let partiesShow = this.state.partiesShow;
		_.map(this.state.handleAccident.data && this.state.handleAccident.data.parties, (item) => {
			arr[item.apInputOrder] = 0;
			partiesShow[item.apInputOrder] = false;
		});
		this.setState({
			time: arr,
			partiesShow: partiesShow,
			number: this.state.handleAccident.data.parties.length,
		});
	}

	countArray(o) {
		let t = typeof o;
		if (t === 'string') {
			return o.length;
		} else if (t === 'object') {
			let n = 0;
			for (let i in o) {
				n++;
			}
			return n;
		}
		return 0;
	}

	goHistory() {
		window.history.go(-1);
	}

	goPush() {
		this.state.handleSendToParty.fetch((json) => {
			this.setState({
				type: 2,
			});
			this.setTime();
		})
	}

	goRefuse(item) {
		let verific = this.state.verific[item.apInputOrder];
		if (verific) {
			this.state.handleRefused.setParam('ac_apId', item.apId).setParam('phone', item.apPartiesPhone).fetch((json) => {
				this.state.isChoice[item.apInputOrder] = 'refuse';
				this.setState({
					show: true,
					isChoice: this.state.isChoice,
				})
			})
		} else {
			this.setState({
				show1: true,
				ac_apId: item.apId,
				phone: item.apPartiesPhone,
				apInputOrder: item.apInputOrder,
			})
		}
	}

	goRefused() {
		let phone = this.state.phone;
		let ac_apId = this.state.ac_apId;
		let apInputOrder = this.state.apInputOrder;
		this.state.handleRefused.setParam('ac_apId', ac_apId).setParam('phone', phone).fetch((json) => {
			this.state.isChoice[apInputOrder] = 'refuse';
			this.setState({
				show1: false,
				isChoice: this.state.isChoice,
			})
		})
	}


	goCancel() {
		this.setState({
			show1: false,
		})
	}

	goSure() {
		let number = this.state.number;
		let len = this.countArray(this.state.isChoice);
		if (number == len) {
			this.state.handlePartyComfirm.fetch((json) => {
				this.overlay.msg('事故处理完成');
				padapp.navigate.linkTo({
					url: '/home'
				})
			})
		} else {
			this.overlay.msg('请获得每位当事人认可并核对完成每位当事人的验证码')
		}
	}

	setTime() {
		let that = this;
		let iTime2 = this.setInterval(() => {
			let time = this.state.time;
			_.map(time, (item, key) => {
				if (time[key] > 0) {
					time[key] -= 1;
				} else {
					time[key] = 0;
				}
			});
			let timeSum = eval(time.join('+'));

			this.setState({
				time: time,
			});
			if (timeSum <= 0) {
				that.clear(iTime2);
				that.setState({
					time: [0, 0],
				})
			}
		}, 1000);
	}

	//重发信息
	goRetransmission(key, phone) {
		this.state.handleSend.setParam('phone', phone).fetch((json) => {
			let time = this.state.time;
			let timeSum = eval(time.join('+'));
			time[key] = 60;
			if (timeSum === 0) {
				this.setState({
					time: time,
				}, () => {
					this.setTime()
				});
			} else {
				this.setState({
					time: time,
				});
			}
		});
	}

	goAgree(key, phone) {
		this.state.handleSend.setParam('phone', phone).fetch((json) => {
			let time = this.state.time;
			let partiesShow = this.state.partiesShow;
			let timeSum = eval(time.join('+'));
			time[key] = 60;
			partiesShow[key] = true;
			if (timeSum === 0) {
				this.setState({
					time: time,
					partiesShow: partiesShow,
				}, () => {
					this.setTime()
				});
			} else {
				this.setState({
					time: time,
					partiesShow: partiesShow,
				});
			}
		});
	}


	setVerific(e, key) {
		let val = e.target.value;
		this.state.verific[key] = val;
		this.setState({
			verific: this.state.verific,
		})
	}

	goCheck(acApId, phone, key) {
		let verific = this.state.verific[key];
		// let partiesShow = this.state.partiesShow;
		// partiesShow[key] = false;
		if (!verific) {
			this.overlay.msg('请输入验证码');
			return;
		}
		this.state.handleComfirm.setParam('ac_apId', acApId).setParam('phone', phone).setParam('code', verific).fetch((json) => {
			console.log(json);
			if (json) {
				this.state.isChoice[key] = 'comfirm';
				this.state.handleAccident.fetch();
				this.setState({
					isChoice: this.state.isChoice,
					// partiesShow: partiesShow,
				})
			}
		})
	}

}