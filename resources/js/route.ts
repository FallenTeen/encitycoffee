type RouteDef = { url: string; method?: string };

const route = {
    edit(): RouteDef {
        return { url: '/settings/profile', method: 'get' };
    },
};

export default route;