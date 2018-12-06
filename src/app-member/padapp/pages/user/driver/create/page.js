module.exports = class {

	onDataLoad() {
		return {
			apiPtText: '请选择',
			bindLicense: this.dataQL('bindLicense'),
			ptid: 0,
		};
	}

	onLoad() {
	}

	goBack() {
		padapp.navigate.pop()
	}

	setVal(e, type) {
		let val = e.target.value;
		this.state.bindLicense.setParam(type, val);
	}

	goChange() {
		if (!this.state.bindLicense.params.certificateNumber) {
			this.overlay.msg('请填写证件号码')
		} else if (!this.state.bindLicense.params.fileNumber) {
			this.overlay.msg('请填写档案编号')
		} else {
			this.state.bindLicense.fetch((data) => {
				if (data) {
					padapp.app.emit('/user.driver.create', {});
					this.overlay.msg('绑定成功');
					padapp.navigate.pop();
				}
			})
		}
	}

};