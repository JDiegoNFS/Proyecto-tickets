# 🤝 Guía de Contribución

¡Gracias por tu interés en contribuir al Sistema de Tickets! Este documento te guiará en el proceso.

## 📋 Código de Conducta

- Sé respetuoso y profesional
- Acepta críticas constructivas
- Enfócate en lo mejor para el proyecto
- Muestra empatía hacia otros colaboradores

## 🚀 Cómo Contribuir

### Reportar Bugs

1. Verifica que el bug no haya sido reportado antes
2. Abre un nuevo Issue con:
   - Título descriptivo
   - Pasos para reproducir
   - Comportamiento esperado vs actual
   - Capturas de pantalla (si aplica)
   - Versión de PHP, MySQL y navegador

### Sugerir Mejoras

1. Abre un Issue describiendo:
   - La funcionalidad propuesta
   - Por qué sería útil
   - Ejemplos de uso
   - Posible implementación

### Pull Requests

1. **Fork el repositorio**
2. **Crea una rama** desde `main`:
   ```bash
   git checkout -b feature/nueva-funcionalidad
   ```

3. **Realiza tus cambios**:
   - Sigue el estilo de código existente
   - Comenta código complejo
   - Actualiza documentación si es necesario

4. **Prueba tus cambios**:
   - Verifica que todo funcione
   - Prueba en diferentes navegadores
   - Prueba con diferentes temas

5. **Commit con mensajes claros**:
   ```bash
   git commit -m "feat: agregar nueva funcionalidad X"
   ```

6. **Push a tu fork**:
   ```bash
   git push origin feature/nueva-funcionalidad
   ```

7. **Abre un Pull Request**:
   - Describe los cambios realizados
   - Referencia Issues relacionados
   - Incluye capturas si hay cambios visuales

## 📝 Estilo de Código

### PHP
```php
// Usar camelCase para variables y funciones
$miVariable = "valor";
function miFuncion() { }

// Usar PascalCase para clases
class MiClase { }

// Comentarios descriptivos
// Esto hace X porque Y
```

### JavaScript
```javascript
// Usar camelCase
const miVariable = 'valor';
function miFuncion() { }

// Usar const/let, no var
const constante = 'valor';
let variable = 'valor';
```

### CSS
```css
/* Usar kebab-case para clases */
.mi-clase {
    /* Propiedades ordenadas alfabéticamente */
    background: #fff;
    color: #000;
    padding: 10px;
}
```

## 🎨 Temas

Si agregas un nuevo tema:
1. Define todas las variables CSS necesarias
2. Prueba en todas las páginas
3. Asegura buen contraste y legibilidad
4. Actualiza la documentación

## 🧪 Testing

Antes de enviar un PR, verifica:
- [ ] El código funciona sin errores
- [ ] No hay warnings de PHP
- [ ] Funciona en Chrome, Firefox y Edge
- [ ] Responsive en móvil y tablet
- [ ] Funciona con todos los temas
- [ ] No rompe funcionalidad existente

## 📚 Documentación

Si agregas nuevas funcionalidades:
- Actualiza el README.md
- Agrega comentarios en el código
- Documenta parámetros y retornos
- Incluye ejemplos de uso

## 🏷️ Convención de Commits

Usa prefijos descriptivos:
- `feat:` Nueva funcionalidad
- `fix:` Corrección de bug
- `docs:` Cambios en documentación
- `style:` Cambios de formato (no afectan código)
- `refactor:` Refactorización de código
- `test:` Agregar o modificar tests
- `chore:` Tareas de mantenimiento

Ejemplos:
```bash
feat: agregar gráfico de tendencias semanales
fix: corregir error en asignación de tickets
docs: actualizar guía de instalación
style: mejorar formato de código en dashboard.php
```

## 🔍 Revisión de Código

Los PRs serán revisados considerando:
- Calidad del código
- Adherencia a estándares
- Funcionalidad correcta
- Impacto en rendimiento
- Compatibilidad

## 💡 Ideas para Contribuir

### Funcionalidades Sugeridas
- [ ] Sistema de notificaciones en tiempo real
- [ ] Exportación de reportes en PDF
- [ ] API REST para integraciones
- [ ] Sistema de comentarios en tickets
- [ ] Búsqueda avanzada de tickets
- [ ] Filtros personalizables
- [ ] Más temas personalizados
- [ ] Modo offline con Service Workers
- [ ] Integración con email
- [ ] Sistema de prioridades

### Mejoras de UX/UI
- [ ] Animaciones más fluidas
- [ ] Mejores mensajes de error
- [ ] Tooltips informativos
- [ ] Atajos de teclado adicionales
- [ ] Modo de accesibilidad mejorado

### Optimizaciones
- [ ] Caché de consultas frecuentes
- [ ] Lazy loading de imágenes
- [ ] Minificación de assets
- [ ] Optimización de consultas SQL

## 📞 Contacto

¿Tienes preguntas? 
- Abre un Issue con la etiqueta `question`
- Contacta al mantenedor del proyecto

## 🙏 Agradecimientos

¡Gracias por contribuir al proyecto! Cada aporte, grande o pequeño, es valioso.

---

**Happy Coding! 🚀**
