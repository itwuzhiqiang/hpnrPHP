module.exports = class {

	onDataLoad(props) {
		return {
			handleMoveCare: this.dataQL('handleMoveCare'),
			rpaId: props.rpaId,
			type: props.type,
		};
	}

	goDesc() {
		this.state.handleMoveCare.setParam('rpaId',this.state.rpaId).fetch((json)=>{
			padapp.navigate.linkTo({
				url: '/handle.desc',
				params: {
					rpaId: this.state.rpaId,
					type: this.state.type,
				}
			})
		});
	}

	goBack() {
		window.history.go(-1);
	}

}