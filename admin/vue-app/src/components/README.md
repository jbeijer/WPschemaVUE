# Component Documentation

## BaseButton

The `BaseButton` component provides a standardized button implementation for the WP Schema Manager plugin. It ensures consistent styling and behavior across the application while providing additional functionality like loading states.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `variant` | String | `''` | Button style variant. Options: `'primary'`, `'secondary'`, `'danger'`, or `''` (default style) |
| `size` | String | `''` | Button size. Options: `'small'` or `''` (default size) |
| `disabled` | Boolean | `false` | Whether the button is disabled |
| `loading` | Boolean | `false` | Whether to show a loading spinner |
| `type` | String | `'button'` | HTML button type. Options: `'button'`, `'submit'`, or `'reset'` |

### Events

| Event | Description |
|-------|-------------|
| `click` | Emitted when the button is clicked (not emitted when disabled or loading) |

### Usage Examples

#### Basic Usage
```vue
<BaseButton>Default Button</BaseButton>
```

#### Variants
```vue
<BaseButton variant="primary">Primary Button</BaseButton>
<BaseButton variant="secondary">Secondary Button</BaseButton>
<BaseButton variant="danger">Danger Button</BaseButton>
```

#### Sizes
```vue
<BaseButton size="small">Small Button</BaseButton>
```

#### Loading State
```vue
<BaseButton :loading="isLoading">
  {{ isLoading ? 'Saving...' : 'Save' }}
</BaseButton>
```

#### Form Submit Button
```vue
<BaseButton variant="primary" type="submit" :loading="isSubmitting">
  {{ isSubmitting ? 'Submitting...' : 'Submit' }}
</BaseButton>
```

#### Disabled State
```vue
<BaseButton :disabled="!isValid">Submit</BaseButton>
```

### Best Practices

1. Use consistent button variants for similar actions across the application:
   - `primary` for main actions
   - `secondary` for alternative actions
   - `danger` for destructive actions

2. Always include loading states for buttons that trigger asynchronous operations

3. Provide clear, concise text that describes the action the button performs

4. Use the `small` size for buttons in tables or other compact UI elements
