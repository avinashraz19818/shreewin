import {
    am as Te,
    at as De,
    t as _e,
    dn as Ee,
    d2 as Pe,
    dp as Ne,
    Y as ke,
    r as L,
    dq as Ge,
    c as n,
    R as H,
    P as Re,
    dr as pe,
    ds as xe,
    dt as Me,
    du as T,
    b8 as He,
    aZ as Ue,
    aU as Oe,
    b0 as m,
    b1 as U,
    a_ as O,
    aY as $e
} from "./index-BgAwOX9Q.js";
import {
    u as Fe
} from "./useWorkerIntervalFn.hook-D7_aQ0_N.js";
import {
    a as We,
    d as Ye,
    e as $,
    f as Ze,
    h as Je,
    i as Qe
} from "./WingoSkeleton.vue_vue_type_style_index_0_scoped_cd4f34e3_lang-CZJCWYiN.js"; /* empty css                                                             */
const Ve = Symbol("AR_LOTTERY");

function nt() {
    return Te(Ve, {})
}

function ut(a) {
    const {
        localStore: f
    } = Me(), h = De(), y = _e(), o = Ee(), {
        synchronizer: k,
        gameCode: c,
        lotteryCode: v,
        gameInfo: w,
        triggerTimer: F,
        trigger: W,
        setLotteryCode: D,
        getGameInfo: G,
        getGameList: Y,
        updateBalance: R,
        getUserInfo: Z,
        onBetTrigger: J,
        token: Q
    } = We(), {
        lotteryInline: V
    } = Pe(), t = ke({
        issue: "",
        issueData: null,
        countdown: 0,
        interval: 0,
        sound: !1,
        soundBg: !1,
        soundEffects: !1,
        agreePreSale: !0,
        introduceDialog: !1,
        betLimit: [],
        introduceHtml: void 0,
        historyIssues: [],
        historyIssuesTotalPage: 0
    }), l = a != null && a.bg ? new Ne.Howl({
        src: a != null && a.bg ? [a.bg] : [],
        loop: !0,
        volume: 1,
        preload: !1
    }) : null, I = L(!1), C = L(!1), b = L(!1), _ = L(), d = (function (m) { var s = m.set.bind(m); m.set = function (k, v) { var r = s(k, v); setTimeout(function () { var done = function () { try { Ae(); } catch (e) {} }; try { var p = B(); if (p && p.then) { p.then(done, done); } else { done(); } } catch (e) { done(); } }, 0); return r; }; return m; })(new Map), q = n(() => t.issue), j = n(() => t.issueData || {}), A = n(() => ({
        interval: t.interval || 0,
        ...xe(t.countdown * 1e3)
    })), z = n(() => {
        const e = `${A.value.minutes}`.padStart(2, "0"),
            s = `${A.value.seconds}`.padStart(2, "0");
        return [...e.split(""), ":", ...s.split("")]
    }), K = n(() => A.value.minutes * 60 + A.value.seconds > ((a == null ? void 0 : a.limitBetTime) || 5)), X = n(() => {
        var e;
        return ((e = w.value) == null ? void 0 : e.state) !== 1
    }), ee = n(() => {
        var e;
        return ((e = w.value) == null ? void 0 : e.betScopes) || []
    }), te = n(() => {
        var e;
        return ((e = w.value) == null ? void 0 : e.betMultiples) || []
    }), se = n(() => {
        var e;
        return ((e = w.value) == null ? void 0 : e.rates) || []
    }), ae = n(() => t.betLimit || []), ne = n({
        get() {
            return t.sound
        },
        set(e) {
            t.sound = e
        }
    }), ue = n({
        get() {
            return t.soundBg
        },
        set(e) {
            t.soundBg = e, t.soundBg ? (f.set(T.SOUND_BG, 1), l && l.load(), l && l.duration(0), l && l.play()) : (l && l.pause(), f.set(T.SOUND_BG, 0))
        }
    }), p = n({
        get() {
            return t.soundEffects
        },
        set(e) {
            e ? f.set(T.SOUND_EF, 1) : f.set(T.SOUND_EF, 0), t.soundEffects = e
        }
    }), re = n(() => t.introduceHtml || {}), oe = n({
        get() {
            return t.agreePreSale
        },
        set(e) {
            t.agreePreSale = e
        }
    }), le = n({
        get() {
            return t.introduceDialog
        },
        set(e) {
            t.introduceDialog = e
        }
    }), E = n(() => t.historyIssues), ie = n(() => t.historyIssuesTotalPage), {
        pause: g,
        resume: ce
    } = Fe(async () => {
        var e, s;
        if (t.countdown < 1) {
            g();
            const u = ((e = t.issueData) == null ? void 0 : e.next) || null,
                {
                    useNext: i = !0
                } = a || {};
            u && i ? (await P(u), t.issueData.next = null) : await S(!0);
            return
        }
        t.countdown -= 1, a && ((s = a == null ? void 0 : a.processSound) == null || s.call(a, t.countdown))
    }, 1e3, {
        immediate: !1
    }), de = n(() => {
        var s;
        const e = (s = w.value) == null ? void 0 : s.webSocketUrl;
        return e ? e + `/connect?token=${Q.value}` : ""
    }), P = async e => {
        var u;
        t.issue = e.issueNumber;
        const s = e.countdown;
        s === 0 ? t.countdown = s + 1 : t.countdown = s, a && ((u = a == null ? void 0 : a.startCallback) == null || u.call(a, s)), ce()
    }, me = () => {
        t.sound = !t.sound
    }, fe = () => {
        t.soundEffects = !t.soundEffects
    }, ge = () => {
        t.introduceDialog = !t.introduceDialog, t.introduceDialog && (I.value = !0)
    }, ye = e => {
        const s = {};
        if (e.current) {
            s.intervalMinute = e.intervalMinute * 60, s.issueNumber = e.current.issueNumber, s.startTime = e.current.startTime, s.endTime = e.current.endTime;
            const u = s.endTime - k.getCurrentTime();
            return s.countdown = Math.floor(u / 1e3), s.gameCode = e.gameCode || null, s
        }
        return e
    }, S = async (e = !1) => {
        try {
            if (b.value) return;
            e && (b.value = !0);
            const {
                result: s,
                data: u,
                ...i
            } = await Ye({
                gameCode: c.value,
                lotteryCode: v.value
            }, !0);
            if (!s && !i.current) return g();
            const r = ye(u || i);
            t.issueData = r, t.interval = r.intervalMinute, await P(r)
        } catch {
            g()
        } finally {
            b.value = !1
        }
    }, ve = async () => {
        try {
            I.value = !0;
            const {
                result: e,
                data: s
            } = await Qe(c.value);
            if (!e) return;
            t.introduceHtml = s
        } catch {} finally {
            I.value = !1
        }
    }, we = async () => {
        if (!C.value) try {
            C.value = !0;
            const {
                result: e,
                data: s
            } = await Je(c.value);
            if (!e) return;
            t.betLimit = s
        } catch {} finally {
            C.value = !1
        }
    }, B = async () => {
        try {
            const {
                result: e,
                data: s
            } = await $({
                gameCode: c.value,
                lotteryCode: v.value
            });
            if (!e) return;
            t.historyIssues = s.list || [], t.historyIssuesTotalPage = s.totalPage || 0
        } catch {} finally {}
    }, be = async e => {
        try {
            const {
                result: s,
                data: u
            } = await $({
                gameCode: c.value,
                lotteryCode: v.value
            });
            if (!s) return;
            const i = u.list || [];
            t.historyIssuesTotalPage = u.totalPage || 0;
            const r = i[0];
            return r.issueNumber !== e ? {
                list: u.list,
                item: null
            } : {
                item: r,
                list: u.list
            }
        } catch {
            return {
                list: [],
                item: null
            }
        }
    }, Ae = async () => {
        try {
            const e = [...d.keys()].reverse();
            if (!e.length) return;
            const s = e[0];
            if (E.value.findIndex(N => N.issueNumber === s) > 0) return d.clear();
            const {
                result: i,
                data: r
            } = await Ze({
                issueNumber: s
            });
            if (!i) return;
            if (r.status === null) {
                d.delete(s);
                return
            }
            if (J(), d.delete(s), !_.value) return;
            const x = r.status === !0;
            let M = E.value.find(N => N.issueNumber === s);
            if (!M) M = { issueNumber: s, number: r.result || "", premium: r.result || "", result: r.result || "", sum: 0, color: "" };
            _.value.open({
                isWin: x,
                amount: r.winAmount || 0,
                issueNumber: s,
                result: M
            }), d.clear(), x && R()
        } catch {}
    }, Se = e => {
        e.length !== 0 && (t.historyIssues = e || [])
    }, he = async () => {
        const e = h.query.gameCode || h.params.gameCode;
        D(e), V.value && pe(), await Promise.all([S(!0), Y(), G(), B()]), setTimeout(async () => {
            await R(), await Z()
        }, 1200)
    }, Ie = async e => {
        b.value || e.state !== 2 && e.gameCode !== c.value && (D(e.gameCode), d.clear(), await y.replace({
            name: v.value,
            params: {
                gameCode: e.gameCode
            }
        }), await Promise.all([G(), S(!0), B()]), W.emit("bets"))
    }, Ce = async () => {
        const e = new Audio;
        e.src = "data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAESsAACJWAAACABAAZGF0YQAAAAA=", e.muted = !0;
        try {
            return await e.play(), !0
        } catch {
            return !1
        }
    }, {
        soundBus: Be,
        syncMuted: Le
    } = Ge();
    return Be.on(e => {
        p.value = !e
    }), H(() => t.soundEffects, e => Le(!e), {
        immediate: !0
    }), Re(() => {
        F.on(() => {
            g()
        })
    }), H(() => o.value, e => {
        e === "hidden" ? g() : setTimeout(async () => {
            await S(!0), await B()
        }, 100)
    }), {
        betScopes: ee,
        betMultiples: te,
        rates: se,
        canBet: K,
        issue: q,
        countdown: A,
        countdownTime: z,
        soundEffects: p,
        soundBg: ue,
        sound: ne,
        gameCode: c,
        agreePreSale: oe,
        introduceDialog: le,
        introduceLoading: I,
        betLimitLoading: C,
        introduceHtml: re,
        bgSound: l,
        issueLoading: b,
        lotteryCode: v,
        betLimit: ae,
        issueData: j,
        historyIssues: E,
        historyIssuesTotalPage: ie,
        mapBet: d,
        winner: _,
        visibility: o,
        websocket: de,
        closeGame: X,
        pause: g,
        getIssue: S,
        updataCurrentIssue: P,
        onSwitchSound: me,
        onSwitchIntroduce: ge,
        getIntroduce: ve,
        getBetLimit: we,
        canAutoPlay: Ce,
        setLotteryCode: D,
        getHistoryIssues: B,
        getWinLossResult: Ae,
        getOpenLottery: be,
        updateHistory: Se,
        getLottery: he,
        onSwitchSoundEffects: fe,
        onLotteryJump: Ie
    }
}
const qe = {
        class: "bet-rule"
    },
    je = {
        class: "bet-rule-head"
    },
    ze = {
        class: "bet-rule-body"
    },
    Ke = {
        class: "bet-rule-foot"
    },
    Xe = He({
        __name: "BetRule",
        props: {
            title: {
                type: String
            }
        },
        emits: ["close"],
        setup(a, {
            emit: f
        }) {
            const h = f;
            return (y, o) => (Oe(), Ue("div", qe, [m("div", je, [o[1] || (o[1] = m("div", {
                class: "sound-dot"
            }, null, -1)), m("span", null, "· " + U(a.title) + " ·", 1), o[2] || (o[2] = m("div", {
                class: "sound-dot"
            }, null, -1))]), m("div", ze, [O(y.$slots, "default", {}, void 0, !0)]), m("div", Ke, [O(y.$slots, "foot", {}, () => [m("div", {
                class: "bet-rule-foot-btn",
                onClick: o[0] || (o[0] = k => h("close"))
            }, U(y.$t("common.close")), 1)], !0)])]))
        }
    }),
    rt = $e(Xe, [
        ["__scopeId", "data-v-cbfdca1d"]
    ]);
export {
    rt as B, Ve as I, nt as a, ut as u
};