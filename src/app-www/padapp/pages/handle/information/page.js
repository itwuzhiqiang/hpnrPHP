module.exports = class {

	onDataLoad(props) {
		return {
			number: 1,
			policeInfo: this.fetchQL('policeInfo'),
			handleConfirmConsultation: this.fetchQL('handleConfirmConsultation', {
				rpaId: props.rpaId,
			}),
			rpaId: props.rpaId,
			type: props.type,
			size: [],
			handleAccident: this.fetchQL('handleAccident', {
				rpaId: props.rpaId,
			}),
            dataServer: padapp.app.getConfig('rpapi'),
		};
	}

	onLoad() {
		this.addListener(('/handle.information.create'), () => {
			this.state.handleConfirmConsultation.fetch((json) => {
				if (json) {
					this.changeNumber();
				}
			});
		})
	}

	onLoaded() {
		this.changeNumber();

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

	onUnLoad() {
		this.overlay.loadingClose(this.load);
	}

	changeNumber() {
		if (this.state.handleConfirmConsultation.data && this.state.handleConfirmConsultation.data.length) {
			let arr = [];
			let arr1 = [];
			let size;
			_.map(this.state.handleConfirmConsultation.data, (item) => {
				arr.push(parseInt(item.apInputOrder))
			});
			size = Math.max.apply(null, arr);
			for (size; size > 0; size--) {
				arr1.push(size);
			}
			let diff = [];
			let tmp = arr1;
			arr.forEach(function (val1, i) {
				if (arr1.indexOf(val1) < 0) {
					diff.push(val1);
				} else {
					tmp.splice(tmp.indexOf(val1), 1);
				}
			});

			let size1 = diff.concat(tmp);
			let number = 0;
			if (size1.length) {
				number = Math.min.apply(null, size1)
			} else {
				number = Math.max.apply(null, arr) + 1
			}
			this.setState({
				number: number,
			})
		} else {
			this.setState({
				number: 1,
			})
		}
	}

	goCreate() {
		let number = this.state.number;
		this.load = this.overlay.shadeLoading();
		padapp.navigate.push({
			url: '/handle/information/create',
			params: {
				number: number,
				rpaId: this.state.rpaId,
			}
		});
	}

	goCreate1(item) {
		this.load = this.overlay.shadeLoading();
		padapp.navigate.push({
			url: '/handle/information/create',
			params: {
				number: item.apInputOrder,
				rpaId: this.state.rpaId,
				data: item,
			}
		});
	}

	goDuty() {
		this.load = this.overlay.shadeLoading();
		padapp.navigate.linkTo({
			url: '/handle.duty',
			params: {
				rpaId: this.state.rpaId,
				type: this.state.type,
			}
		})
	}

	goHistory() {
		window.history.go(-1);
	}


}