// Импортируем зависимости
import "./bootstrap";
import "../css/app.css";
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import Antd from "ant-design-vue";
import "ant-design-vue/dist/reset.css";

// Импорт Pinia
/* import { createPinia } from "pinia"; */

// Импортируем иконки Ant Design
import {
    ExclamationCircleOutlined,
    LockOutlined,
    ArrowLeftOutlined,
    ApartmentOutlined,
    EditOutlined,
    DeleteOutlined,
    CopyOutlined,
    DownOutlined,
    SelectOutlined,
    RollbackOutlined,
    EnterOutlined,
    UserSwitchOutlined,
    SwitcherOutlined,
    CheckSquareOutlined,
    ControlOutlined,
    DollarOutlined,
    FieldTimeOutlined,
    FileTextOutlined,
} from "@ant-design/icons-vue";

// Импортируем Tooltip - всплывающая надпись
import { Tooltip } from "ant-design-vue";

// Основная часть вашего приложения
const appName = import.meta.env.VITE_APP_NAME || "Laravel";

// Создаем Pinia instance
/* const pinia = createPinia(); */

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        return (
            createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue)
                .use(Antd)
                /* .use(pinia) */ // Подключение Pinia
                .component(
                    "ExclamationCircleOutlined",
                    ExclamationCircleOutlined
                )
                .component("LockOutlined", LockOutlined)
                .component("ArrowLeftOutlined", ArrowLeftOutlined)
                .component("ApartmentOutlined", ApartmentOutlined)
                .component("EditOutlined", EditOutlined)
                .component("DeleteOutlined", DeleteOutlined)
                .component("CopyOutlined", CopyOutlined)
                .component("Tooltip", Tooltip)
                .component("DownOutlined", DownOutlined)
                .component("SelectOutlined", SelectOutlined)
                .component("RollbackOutlined", RollbackOutlined)
                .component("EnterOutlined", EnterOutlined)
                .component("UserSwitchOutlined", UserSwitchOutlined)
                .component("SwitcherOutlined", SwitcherOutlined)
                .component("CheckSquareOutlined", CheckSquareOutlined)
                .component("ControlOutlined", ControlOutlined)
                .component("DollarOutlined", DollarOutlined)
                .component("FieldTimeOutlined", FieldTimeOutlined)
                .component("FileTextOutlined", FileTextOutlined)

                .mount(el)
        );
    },
    progress: {
        color: "red",
    },
});
