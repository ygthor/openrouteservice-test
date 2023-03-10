/*
 * This file is part of Openrouteservice.
 *
 * Openrouteservice is free software; you can redistribute it and/or modify it under the terms of the
 * GNU Lesser General Public License as published by the Free Software Foundation; either version 2.1
 * of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with this library;
 * if not, see <https://www.gnu.org/licenses/>.
 */

package org.heigit.ors.api;

import org.heigit.ors.config.AppConfig;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import springfox.documentation.builders.ApiInfoBuilder;
import springfox.documentation.builders.PathSelectors;
import springfox.documentation.builders.RequestHandlerSelectors;
import springfox.documentation.service.ApiInfo;
import springfox.documentation.service.Contact;
import springfox.documentation.spi.DocumentationType;
import springfox.documentation.spring.web.paths.DefaultPathProvider;
import springfox.documentation.spring.web.plugins.Docket;
import springfox.documentation.swagger2.annotations.EnableSwagger2;

@Configuration
@EnableSwagger2
public class SwaggerConfig {
    String swagger_documentation_url = AppConfig.getGlobal().getParameter("info", "swagger_documentation_url");

    ApiInfo apiInfo() {
        return new ApiInfoBuilder()
                .title("Openrouteservice")
                .description("This is the openrouteservice API documentation")
                .license("MIT")
                .licenseUrl("https://github.com/swagger-api/swagger-ui/blob/master/LICENSE")
                .contact(new Contact("", "", "enquiry@openrouteservice.heigit.org"))
                .build();
    }

    @Bean
    public Docket api() {
        return new Docket(DocumentationType.SWAGGER_2)
                .host(swagger_documentation_url)
                .pathProvider(new DefaultPathProvider())
                .select()
                .apis(RequestHandlerSelectors.basePackage("org.heigit.ors.api"))
                .paths(PathSelectors.any())
                .build()
                .apiInfo(apiInfo());
    }
}
