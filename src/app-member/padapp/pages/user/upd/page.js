module.exports = class {

	onDataLoad(props) {
		return {
			type: props.type,
			show: false,
			sex: 0,
			val: 0,
			typeName: '',
			userUpdate: this.dataQL('userUpdate'),
		};
	}

	onLoaded() {
		let type = this.state.type;
		let typeName = this.state.typeName;
		switch (type) {
			case 'sex':
				typeName = '性别';
				break;
			case 'name':
				typeName = '姓名';
				break;
			case 'idCard':
				typeName = '身份证';
				break;
		}
		this.setState({
			typeName: typeName,
		})
	}

	changeSex() {
		this.setState({
			show: !this.state.show,
		})
	}

	changeSex1() {
		this.setState({
			show: !this.state.show,
			val: this.state.sex,
		}, () => {
			this.state.userUpdate.setParam('sex', this.state.val);
		});
	}

	setSex(sex) {
		this.setState({
			sex: sex,
		});
	}

	goBack() {
		padapp.navigate.pop();
	}

	setVal(e) {
		let val = e.target.value;
		let type = this.state.type;
		this.setState({
			val: val,
		});

		this.state.userUpdate.setParam(type, val);
	}

	goChange() {
		if(!this.state.val) {
			this.overlay.msg(this.state.typeName + '不可为空')
		}else{
			this.state.userUpdate.fetch((data)=>{
				console.log(data);
				if (data) {
					padapp.app.emit('/user.upd.update',{});
					this.overlay.msg('修改成功');
					padapp.navigate.pop();
				}
			})
		}
	}

}