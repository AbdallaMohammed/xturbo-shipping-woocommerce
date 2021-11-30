<template>
    <div>
        <div v-if="!loading && enabled">
            <button type="button" class="button-primary" @click="showModal" v-if="shipment === null">
                {{ locale('Prepare XTurbo Shipment', 'xturbo') }}
            </button>
            <button type="button" class="button-primary" @click="showModal" v-if="shipment !== null">
                {{ locale('Shipment Details', 'xturbo') }}
            </button>
        </div>
        <Spinner v-if="loading"></Spinner>
        <modal name="shipment-modal" height="auto" :scrollable="true" :clickToClose="clickToClose">
            <div class="xturbo-modal-dialog" v-if="clickToClose">
                <div class="xturbo-modal-content">
                    <div class="xturbo-modal-header">
                        <div class="modal-header-left">
                            {{ locale('XTurbo', 'xturbo') }}
                        </div>
                    </div>
                    <div class="xturbo-modal-body" v-if="shipment === null">
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Pickup Address', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <input type="text" v-model="form.pickupAddress">
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Receiver Name', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <input type="text" v-model="form.receiverName">
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Receiver Phone', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <input type="text" v-model="form.receiverPhone">
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Deliver Address', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <input type="text" v-model="form.deliverAddress">
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Packaging', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <select v-model="form.packaging">
                                    <option v-for="(packagingType, index) in packagingTypes" :key="index" :value="packagingType.id">
                                        {{ packagingType.text }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Weight', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <input type="number" v-model="form.weight">
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Length', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <input type="number" v-model="form.length">
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Width', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <input type="number" v-model="form.width">
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Height', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <input type="number" v-model="form.height">
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Quantity', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <input type="number" v-model="form.quantity">
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Pickup City', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <select v-model="form.pickupCity">
                                    <option value="0">{{ locale('Select...', 'xturbo') }}</option>
                                    <option v-for="(city, index) in cities" :key="index" :value="city.id">
                                        {{ city.name_ar }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ locale('Delivery City', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <select v-model="form.deliverCity">
                                    <option value="0">{{ locale('Select...', 'xturbo') }}</option>
                                    <option v-for="(city, index) in cities" :key="index" :value="city.id">
                                        {{ city.name_ar }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <div class="xturbo-modal-field-label">
                                <label>{{ xturbo('Notes', 'xturbo') }}</label>
                            </div>
                            <div class="xturbo-modal-field">
                                <textarea v-model="form.note"></textarea>
                            </div>
                        </div>
                        <div class="xturbo-modal-form-row">
                            <button type="button" @click="createOrder" class="button-primary">Submit</button>
                        </div>
                    </div>
                    <div class="xturbo-modal-body" v-if="shipment !== null">
                        <p>
                            <strong>{{ locale('Tracking ID', 'xturbo') }}</strong>: <i>{{ shipment.tracking_id }}</i>
                            <br>
                            <strong>{{ locale('Order Status', 'xturbo') }}</strong>: <i>{{ shipment.status }}</i>
                        </p>
                    </div>
                </div>
            </div>
            <div style="padding: 15px" v-else>
                <Spinner></Spinner>
            </div>
        </modal>
    </div>
</template>

<script>
const { __ } = wp.i18n

import Spinner from 'vue-simple-spinner'

export default {
    data() {
        return {
            enabled: true,
            loading: false,
            xturbo_i18n: xturbo_i18n,
            form: {},
            shipment: null,
            clickToClose: true,
            packagingTypes: [
                {
                    id: 1,
                    text: 'بدون تغليف',
                },
                {
                    id: 4,
                    text: 'نقل مبرد',
                },
                {
                    id: 5,
                    text: 'إضافة كرتون',
                },
            ],
            cities: [],
            locale: __,
        }
    },
    components: {
        Spinner,
    },
    async mounted() {
        await this.getCities()
        await this.getData()
    },
    methods: {
        async getCities() {
            this.loading = true

            const { data } = await this.axios.get(this.xturbo_i18n.admin_ajax, {
                params: {
                    action: 'xturbo_get_cities',
                    nonce: this.xturbo_i18n.nonce,
                }
            })

            if (! data.data || data.data.length <= 0) {
                this.enabled = false
                this.$toast.error(this.locale('XTurbo: Invalid email, or password.', 'xturbo'))
            } else {
                this.cities = data.data
            }
        },
        async getData() {
            this.loading = true

            const urlParams = new URLSearchParams(window.location.search)

            const response = await this.axios.get(this.xturbo_i18n.admin_ajax, {
                params: {
                    action: 'xturbo_get_shipment',
                    order_id: urlParams.get('post'),
                    nonce: this.xturbo_i18n.nonce
                }
            })

            if (typeof response.data.data === 'object') {
                this.shipment = response.data.data
            } else {
                const { data } = await this.axios.get(this.xturbo_i18n.admin_ajax, {
                    params: {
                        action: 'xturbo_get_order_settings',
                        order_id: urlParams.get('post'),
                        nonce: this.xturbo_i18n.nonce,
                    }
                })

                this.form = data.data
                this.form.action = 'xturbo_shipment'
                this.form.nonce = this.xturbo_i18n.nonce
                this.form.order_id = urlParams.get('post')
            }

            this.loading = false
        },
        showModal() {
            this.$modal.show('shipment-modal')
        },
        showPrintModal() {
            this.$modal.show('print-order')
        },
        async createOrder() {
            this.clickToClose = false

            const { data } = await this.axios.get(this.xturbo_i18n.admin_ajax, {
                params: this.form
            }).finally(() => {
                this.clickToClose = true
            })

            if (! data.data.success) {
                this.$toast.error(data.data.message)
            } else {
                this.$toast.success(data.data.message)
                this.$modal.hide('shipment-modal')

                await this.getData()
            }
        },
    },
}
</script>

<style>
.woocommerce-layout__header {
    z-index: 999 !important;
}
.vm--modal {
    text-align: right;
    left: 0px !important;
    margin: 0 auto;
}
</style>