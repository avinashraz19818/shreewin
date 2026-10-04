import {
    dO as W,
    dP as ne,
    dQ as w,
    dt as z,
    du as m,
    dR as oe,
    f as ae,
    dS as q,
    dT as D,
    dU as K,
    a4 as N,
    at as ie,
    c as a,
    r as ce,
    dV as n,
    dW as J,
    am as ue,
    dX as V,
    dx as x,
    cA as le
} from "./index-BgAwOX9Q.js";
class de {
    constructor() {
        this.serviceTime = null, this.serviceTime = null, this.lastSyncTime = 0
    }
    getCurrentTime() {
        if (this.serviceTime === null || this.lastSyncTime === 0) return Date.now();
        const s = Date.now() - this.lastSyncTime;
        return this.serviceTime + s
    }
    syncTime(r) {
        this.serviceTime = r, this.lastSyncTime = Date.now()
    }
}
const S = new de,
    {
        localStore: h
    } = z(),
    Y = ae(),
    k = oe.global.t,
    H = e => {
        h.remove(m.TOKEN);
        const r = localStorage.getItem(N.SASS_LOTTERY);
        localStorage.removeItem(N.SASS_LOTTERY), r || Y.error({
            message: `${k(e?"common.tokenExpired":"common.token")}`,
            onClose() {}
        })
    },
    F = {
        transformRequestHook: ne,
        requestInterceptors: e => {
            var l, g, i;
            e.requestOptions.ts = Date.now(), e.headers || (e.headers = {});
            const r = "",
                s = "",
                c = "";
            return e.url = `${e.baseURL||""}${e.url}`, (l = e.url) != null && l.includes("/kv/") || ((g = e.url) != null && g.includes(".json") ? e.url = e.url : (e.headers.Authorization = `Bearer ${h.get(m.TOKEN) || localStorage.getItem("ar_token") || ""}`, e.url = e.url), (i = e.url) != null && i.includes(".json")) || (e.method === "get" ? e.params = D(Object.assign(e.params || {}, {
                language: h.get(m.LANG) || K
            })) : D(Object.assign(e.data || {}, {
                language: h.get(m.LANG) || K
            }))), e
        },
        responseInterceptors: e => {
            var T;
            const {
                data: r,
                headers: s,
                config: c
            } = e, {
                requestOptions: l
            } = c, g = Date.now() - ((l == null ? void 0 : l.ts) || 0), i = s.authorization || "", y = r.code || e.status;
            if (r.serviceTime && !((T = c.url) != null && T.includes(".json")) && S.syncTime(r.serviceTime + g / 2), [401].includes(y) && H(s.hasOwnProperty("token-expired")), i && ![401].includes(r.code || e.status) && h.set(m.TOKEN, i.replace("Bearer ", "")), [0].includes(r.code)) e.data.result = !0;
            else if (e.data && (e.data.result = !1), e.data.msgCode && ![401].includes(y)) {
                const v = k(`common.code_${e.data.msgCode}`);
                Y.error(`${e.data.msgCode} ${v.includes("common.")?e.data.msg:v}`)
            }
            return e
        },
        responseInterceptorsCatch: async (e, r) => {
            var i;
            const {
                config: s,
                response: c = {}
            } = e, {
                headers: l = {}
            } = c;
            if ([401].includes(((i = c.data) == null ? void 0 : i.code) || c.status) && H(l.hasOwnProperty("token-expired")), !s || !s.requestOptions.retry || (s.retryCount = s.retryCount || 0, s.retryCount >= s.requestOptions.retry.count)) return Promise.reject(e);
            s.retryCount += 1;
            const g = new Promise(y => {
                setTimeout(() => {
                    y(s)
                }, s.requestOptions.retry.delay || 1)
            });
            return s.headers = { ...s.headers,
                "Content-Type": w.Json
            }, g.then(y => r.request(y))
        }
    },
    u = new W({
        authenticationScheme: "Bearer",
        baseURL: "/api",
        timeout: 10 * 1e3,
        withCredentials: !1,
        headers: {
            "Content-Type": w.Json
        },
        transform: F
    }),
    M = new W({
        authenticationScheme: "Bearer",
        baseURL: "",
        timeout: 10 * 1e3,
        withCredentials: !1,
        headers: {
            "Content-Type": w.Json
        },
        transform: F,
        requestOptions: {
            retry: !1
        }
    });

function me(e) {
    return u.get("/Lottery/GetGameInfo", e)
}

function Te({
    gameCode: e,
    lotteryCode: r
}, s = !1) {
    return M.get(s ? `/webapi/kv/issue/${e}` : `/${r}/${e}.json`)
}

function ve(e) {
    return u.get("/Lottery/GetBetLimit", {
        gameCode: e
    })
}

function Ce(e) {
    return u.get("/Lottery/GetGameIntroduce", {
        gameCode: e
    })
}

function Se(e) {
    return u.get("/Lottery/GetRecordPage", e)
}

function ke(e) {
    return u.get("/Lottery/GetHistoryIssuePage", e)
}

function we({
    lotteryCode: e,
    gameCode: r
}) {
    return M.get(`/${e}/${r}/GetHistoryIssuePage.json`)
}

function Ge(e) {
    return u.get("/Lottery/GetTrendStatistics", e)
}

function Ie(e) {
    return u.get("/Lottery/GetWinLossResult", e)
}

function ge() {
    return u.get("/Lottery/GetGameList")
}

function be(e) {
    return u.get("/Lottery/GetWingoLiveUrl", e)
}

function Oe(e) {
    return u.get("/Lottery/GetDragonList", e)
}

function ye() {
    return u.get("/Lottery/GetUserInfo")
}

function fe() {
    return u.get("/Lottery/GetBalance")
}
const {
    localStore: p
} = z(), Le = J("stop"), _e = () => {
    const e = ie(),
        r = J("bet"),
        s = ce(!1),
        c = a(() => n.user),
        l = a(() => n.balance || 0),
        g = a(() => n.gameInfo || {}),
        i = a(() => n.gameCode ? n.gameCode : e.query.gameCode || e.params.gameCode || ""),
        y = a(() => {
            if (n.lotteryCode) return n.lotteryCode;
            const t = i.value;
            return t == null ? void 0 : t.split("_")[0]
        }),
        T = a(() => n.lang),
        C = a(() => n.redirectUrl || p.get(m.REDIRECT_URL) || ""),
        v = a(() => p.get(m.BECK) == "1"),
        Q = a(() => S.getCurrentTime()),
        G = a(() => n.gameList || []),
        I = a(() => n.skin),
        b = a(() => n.skincolor),
        O = a(() => {
            const t = [];
            return n.gameList.forEach(d => {
                t.push(...d.gameList)
            }), t.find(d => d.gameCode === i.value) || {}
        }),
        _ = a(() => n.token || p.get(m.TOKEN) || localStorage.getItem("ar_token") || ""),
        R = a(() => {
            var t;
            return (t = c.value) == null ? void 0 : t.sysCurrency
        }),
        X = a(() => {
            var t;
            return (t = c.value) == null ? void 0 : t.isOpenFollow
        }),
        Z = a(() => {
            const t = e.query.Skin,
                o = p.get(m.SKIN) || n.skin || "",
                d = ["skin_wade", "skin_default"],
                f = ["Classic", "GorgeousDawn"];
            return d.includes(o) ? o : d.includes(t) ? t : (f.includes(o), "skin_wade")
        });

    function ee(t) {
        n.user = t
    }

    function te(t) {
        n.balance = t
    }
    const E = t => {
            const o = t == null ? void 0 : t.split("_")[0];
            n.gameCode = t, n.lotteryCode = o
        },
        U = async t => {
            t.state !== 2 && t.gameCode !== i.value && (E(t.gameCode), await x(600), r.emit("bets"))
        },
        $ = async () => {
            try {
                if (!(p.get(m.TOKEN) || localStorage.getItem("ar_token")) || c.value) return;
                const t = await ye();
                t.result && ee(t.data)
            } catch {}
        },
        j = async () => {
            if (!s.value) try {
                s.value = !0;
                const {
                    result: t,
                    data: o,
                    serviceTime: d
                } = await fe();
                return t && te((o == null ? void 0 : o.balance) || 0), {
                    serviceTime: d
                }
            } catch {
                return {
                    serviceTime: 0
                }
            } finally {
                s.value = !1
            }
        },
        B = async t => {
            if (i.value) try {
                const o = await me({
                    gameCode: t || i.value
                });
                o.result && (n.gameInfo = o.data)
            } catch {}
        },
        A = async (t = !1) => {
            if (!(n.gameList.length && !t)) try {
                const o = await ge();
                if (o.result) {
                    const d = o.data.filter(f => {
                        var L;
                        return ((L = f.gameList) == null ? void 0 : L.length) > 0
                    }).sort((f, L) => L.sort - f.sort);
                    d.forEach(f => {
                        var L;
                        f.gameList = (L = f.gameList) == null ? void 0 : L.sort((se, re) => re.sort - se.sort)
                    }), n.gameList = d
                }
            } catch {}
        },
        P = async () => {
            await x(2e3), r.emit("bets")
        };
    return {
        showBeck: v,
        trigger: r,
        follow: X,
        skin: I,
        skincolor: b,
        triggerTimer: Le,
        synchronizer: S,
        redirectUrl: C,
        lang: T,
        gameList: G,
        currentGame: O,
        serviceTime: Q,
        user: c,
        balance: l,
        state: n,
        gameCode: i,
        lotteryCode: y,
        gameInfo: g,
        token: _,
        lottery_skin: Z,
        balanceLoading: s,
        dollarSign: R,
        setLotteryCode: E,
        updateBalance: j,
        getGameList: A,
        onBetTrigger: P,
        getUserInfo: $,
        getGameInfo: B,
        onLotteryJump: U,
        useProvide: () => {
            le(V, {
                balance: l,
                user: c,
                token: _,
                gameInfo: g,
                balanceLoading: s,
                dollarSign: R,
                gameCode: i,
                gameList: G,
                currentGame: O,
                redirectUrl: C,
                trigger: r,
                skin: I,
                skincolor: b,
                updateBalance: j,
                getGameList: A,
                onBetTrigger: P,
                getUserInfo: $,
                getGameInfo: B,
                onLotteryJump: U,
                lotteryCode: y
            })
        }
    }
}, Re = () => ue(V, {});
export {
    _e as a, Ge as b, ke as c, Te as d, we as e, Ie as f, Se as g, ve as h, Ce as i, be as j, Oe as k, fe as l, u as r, Re as u
};