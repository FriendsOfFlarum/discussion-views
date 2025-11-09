import { BooleanGambit } from 'flarum/common/query/IGambit';
export default class PopularGambit extends BooleanGambit {
    key(): string;
    filterKey(): string;
}
