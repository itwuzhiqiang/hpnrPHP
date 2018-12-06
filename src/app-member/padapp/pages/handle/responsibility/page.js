module.exports = class {

	onDataLoad(props) {
		return {
			tem: false,
			show: false,
			template: '',
			rpaId: props.rpaId,
			type: props.type,
			partyInputs: props.partyInputs,
			// rpaMangerInstructions: props.rpaMangerInstructions,
			// rpaManger: props.rpaManger,
			// rpaMangerText: props.rpaMangerText,
			// rpaReasonCode: props.rpaReasonCode,
			rpaResponsibility: '',
			data: props.data,
			handleAccident: this.fetchQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			handleCognizance: this.dataQL('handleCognizance', {
				rpaId: props.rpaId,
			}),
			handleUnCollection: this.dataQL('handleUnCollection', {
				rpaId: props.rpaId,
			}),
			userInfo: this.fetchQL('userInfo'),
		};
	}

	onLoad() {
		this.addListener('/handle.responsibility.template', (data) => {
			this.setState({
				template: data.data.templateContent,
			})
		});
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
		$('.inputf').on('focus', function () {
			var __this = this;
			setTimeout(function () {
				__this.scrollIntoViewIfNeeded();
			}, 0)
		});
		let rpaManger = this.state.rpaManger;
		let data = JSON.parse(this.state.data);
		// let rpaReasonCode = this.state.rpaReasonCode;
		// let rpaMangerText = this.state.rpaMangerText;
		let rpaResponsibility = this.state.rpaResponsibility;
		let partyInputs = JSON.parse(this.state.partyInputs);
		let time = this.state.handleAccident.data.accident.rpaOccurrenceTime;
		let address = this.state.handleAccident.data.accident.rpaAddress;
		let timearr = time.replace(" ", ":").replace(/\:/g, "-").split("-");
		console.log(data);
		console.log(partyInputs);
		rpaResponsibility = timearr[0] + '年' + timearr[1] + '月' + timearr[2] + '日' + ' ' + timearr[3] + '时' + timearr[4] + '分许,';
		function descend(x, y) {
			return y['apRt'] - x['apRt'];  //按照责任降序排列
		}

		partyInputs.sort(descend);
		let len = partyInputs.length;
		let i = 0;
		let arr = [];
		let str = [];

		rpaResponsibility = rpaResponsibility + '驾驶人' + partyInputs[i].apPartiesName + '驾驶' + partyInputs[i].aviPlateNumber
			+ '小型汽车行至' + address + '时，' + (data.data.atSituation ? data.data.atSituation : '');
		for (i; i < len; i++) {
			// console.log(i)
			if (i !== 0) {
				arr.push('驾驶人' + partyInputs[i].apPartiesName + '驾驶' + partyInputs[i].aviPlateNumber + '小型汽车')
			}
			if (partyInputs[i].apRt == 1) {
				str.push(partyInputs[i].apPartiesName + '不承担该起事故的责任')
			} else {
				str.push(partyInputs[i].apPartiesName + '承担该起事故的' + partyInputs[i].apRtText)
			}
		}
		arr = arr.join('、');
		str = str.join('、');
		if (this.state.type != 1) {
			rpaResponsibility = rpaResponsibility + ' 与 ' + arr + '相撞，';
		}
		rpaResponsibility = rpaResponsibility + ' 造成车辆受损的交通事故。' + '根据' + (data.data.atStatute ? data.data.atStatute : '其它') + '之规定，' + str + '。';

		console.log(rpaResponsibility);
		this.setState({
			rpaResponsibility: rpaResponsibility
		})
	}


	goTemplate() {
		this.setState({
			tem: !this.state.tem,
		});
		if (!this.state.tem) {
			padapp.navigate.push({
				url: '/handle/responsibility/template',
			})
		} else {
			this.setState({
				template: '',
			})
		}
	}

	onChangeVal(e, type) {
		let val = e.target.value;
		if (type === 1) {
			this.setState({
				rpaResponsibility: val,
			})
		} else {
			this.setState({
				template: val,
			})
		}

	}

	goSure() {
		this.setState({
			show: true,
		})
	}

	goCancel() {
		this.setState({
			show: false,
		})
	}

	goAccident() {
		this.state.handleUnCollection
			.setParam('partyInputs', this.state.partyInputs)
			.setParam('rpaResponsibility', this.state.rpaResponsibility)
			.setParam('rapConciliation', this.state.template)
			.setParam('accident', this.state.data)
			.fetch((json) => {
				// if (json) {
				padapp.navigate.linkTo({
					url: '/handle.detail',
					params: {
						rpaId: this.state.rpaId,
						type: this.state.type,
					}
				})
				// }
			})

	}

	goHistory() {
		window.history.go(-1);
	}

}