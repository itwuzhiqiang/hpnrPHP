module.exports = class {

	onDataLoad(props) {
		return {
			msgQueryByType: this.fetchQL('msgQueryByType', {
				typeId: props.typeId,
			}),
			typeName: props.typeName,
			nextPage: false,
			pageNum: 1,
			userInfo: this.fetchQL('userInfo'),
		};
	}

	onLoaded() {

		let code = this.state.userInfo.data.code;
		if (code === -1) {
			this.overlay.msg('登录失效');
			padapp.navigate.linkTo({
				url: '/login',
			})
		}
		let nextPage = this.state.msgQueryByType.data.hasNextPage;
		let pageNum = this.state.msgQueryByType.data.pageNum;
		this.setState({
			nextPage: nextPage,
			pageNum: pageNum,
		})
	}

	nextPage() {
		this.state.msgQueryByType.setParam().fetch((data)=>{
			this.setState({
				nextPage: data.hasNextPage,
				pageNum: data.pageNum,
			})
		})
	}

	goBack() {
		padapp.navigate.pop();
	}

}