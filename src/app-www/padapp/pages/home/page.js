module.exports = class {

	onDataLoad() {
		return {
            dataServer: padapp.app.getConfig('rpapi'),
			date: '',
			weather: '',
			points: {},
            policeInfo: this.fetchQL('policeInfo'),
            currenturl:'',
		};
	}


	onLoaded() {
		let that = this;
		let myDate = new Date();
		let y = myDate.getFullYear();
		let m = myDate.getMonth() + 1;
		let d = myDate.getDate();
		let d1 = myDate.getDay();
		let date = this.state.date;
		let weather = this.state.weather;
		let arr = ['日', '一', '二', '三', '四', '五', '六'];
		date = y + '年' + m + '月' + d + '日' + ' ' + '星期' + arr[d1];

		$.getScript('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js', function (_result) {
			if (remote_ip_info.ret == '1') {
				$.ajax({
					type: "GET",
					url: "http://wthrcdn.etouch.cn/weather_mini?city=" + remote_ip_info.city,
					data: "",
					success: function (msg) {
						msg = JSON.parse(msg);
						if (msg.status == 1000) {
							let arr = msg.data.forecast[0].high.split(' ');
							weather = msg.data.forecast[0].type + arr[1];
							that.setState({
								date: date,
								weather: weather,
							})
						}
					}
				});
			}
		});

		let mapObj, geolocation;
		//加载地图，调用浏览器定位服务
		mapObj = new AMap.Map('iCenter');
		mapObj.plugin('AMap.Geolocation', function () {
			geolocation = new AMap.Geolocation({
				enableHighAccuracy: true,//是否使用高精度定位，默认:true
				timeout: 10000,          //超过10秒后停止定位，默认：无穷大
				maximumAge: 0,           //定位结果缓存0毫秒，默认：0
				convert: true,           //自动偏移坐标，偏移后的坐标为高德坐标，默认：true
				showButton: false,        //显示定位按钮，默认：true
				buttonPosition: 'LB',    //定位按钮停靠位置，默认：'LB'，左下角
				buttonOffset: new AMap.Pixel(10, 20),//定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)
				showMarker: false,        //定位成功后在定位到的位置显示点标记，默认：true
				showCircle: false,        //定位成功后用圆圈表示定位精度范围，默认：true
				panToLocation: false,     //定位成功后将定位到的位置作为地图中心点，默认：true
				zoomToAccuracy: false      //定位成功后调整地图视野范围使定位位置及精度范围视野内可见，默认：false
			});
			mapObj.addControl(geolocation);
			geolocation.getCurrentPosition();
			AMap.event.addListener(geolocation, 'complete', (data) => {
				let ggPoint = new BMap.Point(data.position.getLng(), data.position.getLat());
				let convertor = new BMap.Convertor();//百度坐标转换
				let pointArr = [];
				pointArr.push(ggPoint);
				convertor.translate(pointArr, 3, 5, (bdata) => {
					that.setState({
						points: bdata.points[0],
					})
				});
			});//返回定位信息
			AMap.event.addListener(geolocation, 'error', () => {
				that.overlay.msg('定位失败,请使用地图定位')
			});      //返回定位出错信息
		});

        $.ajax({
            type: 'GET',
            dataType:'json',
            url:that.state.dataServer + '/accident/v1/app/accident/history/selectMHistoryRecord?accountToken='  + that.state.policeInfo.data.sign,
            success: function(msg) {
            	console.log(msg);
            	let datad =  msg.data;
                that.currenturl = datad.html;
				if (datad.html != null){
                    document.getElementById('showdiv').style.display = 'block';
                    document.getElementById('cover').style.display = 'block';
				}
				else {
					console.log("暂无信息");
				}
            },
			error: function() {
                console.log("请求出错处理");
            }
        });
	}

	goBeResponsibility() {
		this.overlay.alert('暂未开放1111')
	}

	goUser() {
		padapp.navigate.linkTo({
			url: '/user.index'
		})
	}

	goHandle() {
		// console.log(this.state.points);return
		padapp.navigate.linkTo({
			url: '/handle.remind',
			params: {
				points: JSON.stringify(this.state.points),
			}
		})
	}

	goSearch() {
		padapp.navigate.linkTo({
			url: '/search.list'
		})
	}

	goHistory() {
		padapp.navigate.linkTo({
			url: '/accident.history'
		})
	}

	goComment() {
		this.overlay.msg('尚未开放');
		return;
		padapp.navigate.linkTo({
			url: '/accident.comment'
		})
	}
    confirm(){
		let that = this;
        window.location.href = that.currenturl;

	}
    cancel(){
        document.getElementById('showdiv').style.display = 'none';
        document.getElementById('cover').style.display = 'none';
	}
}