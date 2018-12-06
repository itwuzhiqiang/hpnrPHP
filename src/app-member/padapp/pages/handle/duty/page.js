module.exports = class {

	onDataLoad(props) {
		return {
			show: false,
			accidentTitle: '请选择',
			handleConfirmConsultation: this.fetchQL('handleConfirmConsultation', {
				rpaId: props.rpaId,
			}),
			handleAccident: this.fetchQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			handleCognizance: this.dataQL('handleCognizance', {
				rpaId: props.rpaId,
			}),
			rpaId: props.rpaId,
			type: props.type,
			responsibilityType: [],
			partyInputs: [],
			data: [],
			rpaReasonCode: '',
			rpaResponsibility: '',
			rpaManger: '',
			rpaMangerText: '',
			rpaMangerInstructions: '',
			userInfo: this.fetchQL('userInfo'),
		};
	}

	onLoad() {
		this.addListener('/handle.duty.accident', (data) => {
			console.log(data);
			this.setState({
				accidentTitle: data.data.atName,
				rpaReasonCode: data.data.atCode,
				// rpaResponsibility: data.data.atResponsibility,
				rpaMangerInstructions: data.data.atRegulations,
				rpaManger: data.data.atId,
				rpaMangerText: data.data.atName,
				data: data,
			});
			this.state.handleCognizance
				.setParam('rpaReasonCode', data.data.atCode)
				.setParam('rpaResponsibility', data.data.atResponsibility)
				.setParam('rpaMangerInstructions', data.data.atStatute)
				.setParam('rpaManger', data.data.atName)
				.setParam('rpaMangerText', data.data.atRegulations);
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

		let arr = this.state.partyInputs;
		_.map(this.state.handleConfirmConsultation && this.state.handleConfirmConsultation.data, (item) => {
			arr.push({
				apRt: 0,
				apRtText: '',
				apiApId: item.apId,
				aviPlateNumber: item.aviPlateNumber,
				apPartiesName: item.apPartiesName,
				apiAvId: item.aviId,
			})
		});

		this.setState({
			partyInputs: arr,
		})
	}

	goBackstage() {
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
		padapp.navigate.push({
			url: '/handle/duty/accident',
			params: {
				rpaId: this.state.rpaId,
				type: this.state.type,
			}
		})
	}

	goSure() {
		padapp.navigate.linkTo({
			url: '/handle.shot5',
			params: {
				rpaId: this.state.rpaId,
				type: this.state.type,
			}
		})
	}

	goResponsibility() {
		let arr = this.state.partyInputs;
		let data = this.state.data;
		let rpaReasonCode = this.state.rpaReasonCode;
		let rpaMangerText = this.state.rpaMangerText;
		let rpaManger = this.state.rpaManger;
		let rpaMangerInstructions = this.state.rpaMangerInstructions;
		let sure = true;
		if (!rpaReasonCode) {
			this.overlay.msg('请选择事故情形');
			return;
		}
		_.map(arr, (item) => {
			if (!item.apRt) {
				sure = false;
				this.overlay.msg('请为当事人定责');
			}
		});
		if (sure) {
			padapp.navigate.linkTo({
				url: '/handle.responsibility',
				params: {
					rpaId: this.state.rpaId,
					type: this.state.type,
					partyInputs: JSON.stringify(arr),
					data: JSON.stringify(data),
					// rpaMangerInstructions: rpaMangerInstructions,
					// rpaManger: rpaManger,
					// rpaMangerText: rpaMangerText,
					// rpaReasonCode: rpaReasonCode,
				}
			})
		}
	}

	goHistory() {
		window.history.go(-1);
	}

	setResponsibilityType(responsibilityType, key, name) {
		let arr = this.state.partyInputs;
		_.map(arr, (item) => {
			if (item.apiApId == key) {
				item.apRt = responsibilityType;
				item.apRtText = name;
			}
		});

		// console.log(arr);
		this.setState({
			partyInputs: arr,
		});
		this.state.responsibilityType[key] = responsibilityType;
		this.setState({
			responsibilityType: this.state.responsibilityType,
		})
	}

}