<script setup>
    import { inject, ref, computed } from 'vue';
    import { useI18n } from "vue-i18n";
    import { useRouter, useRoute } from 'vue-router';

    import WebApp from '@twa-dev/sdk';

    import Header from '../components/Header.vue';

    import Utils from '../utils';

    // If user has no saved wallet, redirect to login
    if (!(Utils.GetWallet('wallet'))) {

        const router = useRouter();
        router.push('/login');

    }

    const i18nLocale = useI18n({ useScope: 'global' });

    // Set i18n locale based on the user's locale provided by <LocaleProvider>
    i18nLocale.locale.value = localStorage.getItem('dpxwallet_locale') || inject('locale', 'en');

    const status = ref('normal');

    const route = useRoute();
    const router = useRouter();

    const wallet = ref(Utils.GetWallet('wallet'));
    const secret = ref(Utils.GetWallet('secret'));

    const destination = ref(route.params.destination);
    const amount = ref(0);

    const ScanQRCode = () => {

        Utils.ScanQRCode(i18nLocale.t('wallet.scan_wallet'), (data) => {

            if (/^[A-Fa-f0-9]{42}$/.test(data.data) || /^[A-Fa-f0-9]{42}\/(([0-9]*[.]?)[0-9]?)$/.test(data.data)) {

                if (/^[A-Fa-f0-9]{42}\/(([0-9]*[.]?)[0-9]?)$/.test(data.data)) {

                    destination.value = data.data.toString().split('/')[0];
                    amount.value = data.data.toString().split('/')[1];

                } else {

                    destination.value = data.data;

                }

                return true;

            }

            return false;

        });

    };

    const Submit = async () => {

        Utils.Prompt(i18nLocale.t('transfer.prompt.verify_transfer.title'), i18nLocale.t('transfer.prompt.verify_transfer.text', { amount: amount.value, destination: destination.value, fee: (import.meta.env.VITE_FEE || (0.2)) }), [
            {
                text: i18nLocale.t('general.no'),
                type: "default",
                event: () => { window.Telegram.WebApp.HapticFeedback.impactOccurred('soft'); },
            },
            {
                text: i18nLocale.t('general.yes'),
                type: "destructive",
                event: async () => {

                    status.value = 'progress';
                    window.Telegram.WebApp.HapticFeedback.impactOccurred('medium');

                    let result = await Utils.DPXSendRequest('/transfer', { 'departure': wallet.value, 'secret': secret.value, 'destination': destination.value, 'amount': amount.value }, 'POST', i18nLocale);

                    if (result) {

                        window.Telegram.WebApp.HapticFeedback.notificationOccurred('success');

                        status.value = 'success';
                        Utils.PlayAudio('Success.mp3');

                        Utils.Toast(i18nLocale.t('transfer.toast.success'), 2500);

                        localStorage.setItem(`transaction_${result.result.transaction}`, JSON.stringify(result.result));

                        setTimeout(() => {

                            router.push(`/transaction/${result.result.transaction}`);

                        }, 2500);

                    } else {

                        window.Telegram.WebApp.HapticFeedback.notificationOccurred('error');

                        status.value = 'failed';
                        Utils.PlayAudio('Failed.mp3');

                        setTimeout(() => { status.value = 'normal'; }, 2500);

                    }

                },
            },
        ]);

    };

    WebApp.BackButton.onClick(() => { router.push('/'); });
    WebApp.BackButton.show();

    const isValidDestination = computed(() => {
        return /^0x[a-fA-F0-9]{40}$/.test(destination.value);
    });

    const isValidAmount = computed(() => {
        const parsedAmount = parseFloat(amount.value);
        return parsedAmount > 0 && !isNaN(parsedAmount);
    });

    const isValidTransfer = computed(() => {
        return isValidDestination.value && isValidAmount.value;
    });

</script>

<template>
    <section id="section-transfer">

        <Header icon="icon-upload" :title="$t('transfer.title')"></Header>

        <div>

            <div class="form-item">
                <label>{{ $t('transfer.fields.departure') }}</label>
                <div>
                    <input type="text" enterkeyhint="done" :placeholder="$t('transfer.fields.departure')" v-model="wallet"
                        @keydown="Utils.hideKeyboardOnEnter" disabled />
                </div>
            </div>

            <div class="form-item">
                <label>{{ $t('transfer.fields.destination') }}</label>
                <div>
                    <input type="text" enterkeyhint="done" :placeholder="$t('transfer.fields.destination')" v-model="destination"
                        @keydown="Utils.hideKeyboardOnEnter" />
                    <i @click="ScanQRCode('destination')" class="icon-maximize"></i>
                </div>
            </div>

            <div class="form-item">
                <label>{{ $t('transfer.fields.amount') }}</label>
                <div>
                    <input type="text" enterkeyhint="done" :placeholder="$t('transfer.fields.transfer_amount')"
                        v-model="amount" @keydown="Utils.hideKeyboardOnEnter" />
                </div>
            </div>

            <div id="container-button">
                <button 
                    :class="['button', 'button-progress', 'normal', `button-progress-${status}`]" 
                    @click="Submit"
                    :disabled="!isValidTransfer"
                >
                    <i class="icon-upload"></i>
                    <span>{{ $t('transfer.request_transfer') }}</span>
                </button>
            </div>

        </div>

    </section>
</template>

<style lang="scss">

    #section-transfer {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        height: 100%;

        >div {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            flex-grow: 1;

            #container-button {
                width: 100%;
                flex-grow: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-end;
            }
        }
    }
    
</style>