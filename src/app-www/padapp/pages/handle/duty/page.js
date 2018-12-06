module.exports = class {

	onDataLoad(props) {
		return {
			show: false,
			accidentTitle: '请选择',
			handleConfirmConsultation: this.fetchQL('handleConfirmConsultation', {
				rpaId: props.rpaId,
			}),
			// handleAccident: this.fetchQL('handleAccident', {
			// 	rpaId: props.rpaId,
			// }),
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
            dataServer: padapp.app.getConfig('rpapi'),
            policeInfo: this.fetchQL('policeInfo'),
		};
	}

	onLoad() {
		this.addListener('/handle.duty.accident', (data) => {
			console.log(data);
			this.setState({
				accidentTitle: data.data.atName,
				rpaReasonCode: data.data.atCode,
				// rpaResponsibility: data.data.atResponsibility,
				rpaMangerInstructions: data.data.atRegulations ? data.data.atRegulations : '其它',
				rpaManger: data.data.atId,
				rpaMangerText: data.data.atName,
				data: data,
			});
			this.state.handleCognizance
				.setParam('rpaReasonCode', data.data.atCode)
				.setParam('rpaResponsibility', data.data.atResponsibility)
				.setParam('rpaMangerInstructions', data.data.atStatute ? data.data.atStatute : '其它')
				.setParam('rpaManger', data.data.atName)
				.setParam('rpaMangerText', data.data.atRegulations ? data.data.atRegulations : '其它');
		});
	}

	onLoaded() {
		let arr = this.state.partyInputs;
		_.map(this.state.handleConfirmConsultation && this.state.handleConfirmConsultation.data, (item) => {
			arr.push({
				apRt: 0,
				apRtText: '',
				apiApId: item.apId,
				aviPlateNumber: item.aviPlateNumber,
				apPartiesName: item.apPartiesName,
				// apiAvId: item.aviId,
			})
		});

		this.setState({
			partyInputs: arr,
		});

        let that = this;
        let currenturl = window.location.href;
        $.ajax({
            url:that.state.dataServer + '/accident/v1/app/accident/history/addMHistoryRecord?html=' + currenturl + '&accountToken='  + that.state.policeInfo.data.sign,
            type:'POST',
            data: currenturl,
            cache: false,
            processData: false,
            contentType: false
        }).done((res)=>{
            console.log("success");
        }).fail(function (ress) {
            console.log("fail");

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
		})
	}

	goSure() {
		padapp.navigate.linkTo({
			url: '/home'
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

			// this.state.handleCognizance.setParam('partyInputs',this.state.partyInputs).fetch((json)=>{
			// 	padapp.navigate.linkTo({
			// 		url: '/handle.responsibility',
			// 		params: {
			// 			rpaId: this.state.rpaId,
			// 			type: this.state.type,
			// 		}
			// 	})
			// })

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